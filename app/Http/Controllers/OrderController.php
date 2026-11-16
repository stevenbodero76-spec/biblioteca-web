<?php

namespace App\Http\Controllers;

use App\Models\Order;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Order::class);

        $user = $request->user();
        $filtros = $request->only(['buscar', 'estado_pago', 'estado_pedido']);

        $orders = Order::with(['items.book', 'reader', 'user'])
            ->visiblePara($user)
            ->when(trim((string) ($filtros['buscar'] ?? '')), function ($q, $buscar) {
                $q->where(function ($sub) use ($buscar) {
                    $sub->where('codigo_orden', 'like', "%{$buscar}%")
                        ->orWhere('codigo_operacion', 'like', "%{$buscar}%")
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$buscar}%")->orWhere('email', 'like', "%{$buscar}%"));
                });
            })
            ->when($filtros['estado_pago'] ?? null, fn ($q, $p) => $q->where('estado_pago', $p))
            ->when($filtros['estado_pedido'] ?? null, fn ($q, $e) => $q->where('estado_pedido', $e))
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('orders.index', compact('orders', 'filtros'));
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        $order->load(['items.book', 'items.loan', 'reader', 'user']);

        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => true,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
        ]);

        $payload = json_encode([
            'orden' => $order->codigo_orden,
            'cliente' => $order->user->name,
            'total' => (string) $order->total,
            'pago' => $order->etiquetaEstadoPago(),
            'pedido' => $order->etiquetaEstadoPedido(),
        ], JSON_UNESCAPED_UNICODE);

        $qrDataUri = (new QRCode($options))->render($payload);

        return view('orders.show', compact('order', 'qrDataUri'));
    }

    public function confirmPayment(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('manage', Order::class);

        $datos = $request->validate([
            'codigo_operacion' => ['nullable', 'string', 'max:60'],
        ]);

        if ($order->estado_pago !== 'pendiente') {
            return back()->with('error', 'Esta orden ya no tiene pago pendiente.');
        }

        DB::transaction(function () use ($order, $datos) {
            /** @var Order $locked */
            $locked = Order::with('items.loan')->lockForUpdate()->findOrFail($order->id);
            if ($locked->estado_pago !== 'pendiente') {
                return;
            }

            $codigo = trim((string) ($datos['codigo_operacion'] ?? '')) ?: $locked->codigo_operacion;

            $locked->update([
                'estado_pago' => 'pagado',
                'estado_pedido' => $locked->estado_pedido === 'pendiente' ? 'confirmado' : $locked->estado_pedido,
                'codigo_operacion' => $codigo,
                'fecha_pago' => now(),
                'expira_pago_at' => null,
            ]);

            foreach ($locked->items as $item) {
                if ($item->loan && $item->loan->estado_pago === 'pendiente') {
                    $item->loan->update([
                        'estado_pago' => 'pagado',
                        'codigo_operacion' => $codigo,
                        'fecha_pago' => now(),
                        'expira_pago_at' => null,
                    ]);
                }
            }
        });

        return back()->with('success', "Pago de orden {$order->codigo_orden} verificado y confirmado.");
    }

    public function updateStatus(Request $request, Order $order): RedirectResponse
    {
        Gate::authorize('manage', Order::class);

        $datos = $request->validate([
            'estado_pedido' => ['required', 'in:pendiente,confirmado,entregado'],
        ]);

        if ($order->estado_pedido === 'cancelado' || $order->estado_pago === 'anulado') {
            return back()->with('error', "No se puede cambiar el estado de la orden {$order->codigo_orden} porque fue anulada.");
        }

        if ($datos['estado_pedido'] === 'entregado' && $order->estado_pago !== 'pagado') {
            return back()->with('error', "Debes confirmar el pago de {$order->codigo_orden} antes de marcarla como entregada.");
        }

        $order->update(['estado_pedido' => $datos['estado_pedido']]);

        return back()->with('success', "Estado logístico de {$order->codigo_orden} actualizado.");
    }

    public function cancel(Order $order): RedirectResponse
    {
        Gate::authorize('manage', Order::class);

        if ($order->estado_pedido === 'cancelado' || $order->estado_pago === 'anulado') {
            return back()->with('error', 'La orden ya se encuentra anulada.');
        }

        DB::transaction(function () use ($order) {
            /** @var Order $locked */
            $locked = Order::with(['items.book', 'items.loan', 'reader'])->lockForUpdate()->findOrFail($order->id);

            $locked->update([
                'estado_pago' => 'anulado',
                'estado_pedido' => 'cancelado',
                'expira_pago_at' => null,
            ]);

            foreach ($locked->items as $item) {
                if ($item->tipo_operacion === 'venta' && $item->book) {
                    $item->book->restaurarStockYNotificar((int) $item->cantidad, true);
                } elseif ($item->loan && $item->loan->estado !== 'devuelto' && ! $item->loan->estaAnulado()) {
                    $item->loan->update([
                        'estado' => 'devuelto',
                        'estado_pago' => 'anulado',
                        'expira_pago_at' => null,
                    ]);
                    $item->book?->restaurarStockYNotificar(1, false);
                }
            }

            if ($locked->puntos_karma_usados > 0 && $locked->reader) {
                $locked->reader->registrarKarma(
                    (int) $locked->puntos_karma_usados,
                    "Reembolso de Puntos Nova por anulación de orden {$locked->codigo_orden}",
                    'order',
                    $locked->id
                );
            }
        });

        return back()->with('success', "Orden {$order->codigo_orden} anulada y stock/puntos restablecidos.");
    }
}
