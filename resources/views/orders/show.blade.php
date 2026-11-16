@extends('layouts.app')

@section('title', 'Orden ' . $order->codigo_orden . ' · NOVAETHER™')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="no-print flex items-center justify-between mb-6">
        <a href="{{ route('orders.index') }}" class="text-xs font-extrabold uppercase text-amber-400 hover:underline">← Volver a Pedidos</a>
        <button type="button" onclick="window.print()" class="bg-gradient-to-r from-amber-400 to-orange-500 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl shadow">
            🖨️ Imprimir / PDF
        </button>
    </div>

    <div class="print-card bg-slate-900 border border-slate-700 rounded-3xl overflow-hidden shadow-2xl">
        <div class="bg-slate-950 px-6 py-5 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-amber-400">Comprobante de Orden NOVAETHER™</p>
                <h1 class="text-2xl font-extrabold text-white">{{ $order->codigo_orden }}</h1>
                <p class="text-xs text-slate-300">{{ $order->created_at->format('d/m/Y H:i') }} · {{ $order->user->name }}</p>
            </div>
            <div class="text-right space-y-1">
                <span class="inline-block px-3 py-1 rounded-full text-xs font-black {{ $order->estado_pago === 'pagado' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : ($order->estado_pago === 'anulado' ? 'bg-rose-500/20 text-rose-200' : 'bg-amber-500/20 text-amber-300 border border-amber-400/40') }}">
                    {{ $order->etiquetaEstadoPago() }}
                </span>
                <span class="block text-xs text-slate-300">Logística: <strong>{{ $order->etiquetaEstadoPedido() }}</strong></span>
            </div>
        </div>

        <div class="p-6 space-y-6">
            @if($order->estado_pago === 'pendiente' && $order->expira_pago_at)
                <div class="rounded-2xl bg-amber-950/50 border border-amber-400/50 p-4 text-xs text-amber-200">
                    ⏳ <strong>Pago pendiente de validación en caja.</strong> Stock reservado hasta el {{ $order->expira_pago_at->format('d/m/Y H:i') }}.
                    @if($order->codigo_operacion)
                        Código informado: <strong class="text-white">{{ $order->codigo_operacion }}</strong>.
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                <div class="md:col-span-8 space-y-3">
                    <h2 class="text-xs font-black uppercase tracking-wider text-amber-400">Obras Incluidas</h2>
                    @foreach($order->items as $item)
                        <div class="bg-slate-950 rounded-xl border border-slate-800 p-3 flex items-center justify-between gap-3">
                            <div>
                                <p class="font-bold text-sm text-white">{{ $item->book->titulo }}</p>
                                <p class="text-xs text-slate-300">
                                    {{ $item->tipo_operacion === 'venta' ? "Compra directa × {$item->cantidad}" : "Alquiler ({$item->duracion_dias} días)" }}
                                </p>
                                @if($item->loan)
                                    <a href="{{ route('loans.ticket', $item->loan) }}" class="inline-block mt-1 text-[11px] font-bold text-amber-400 hover:underline">
                                        🎫 Abrir Ticket QR de alquiler ({{ $item->loan->codigoTicket() }}) →
                                    </a>
                                @endif
                            </div>
                            <span class="font-black text-sm text-amber-300">S/ {{ number_format((float) $item->subtotal, 2) }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="md:col-span-4 flex flex-col items-center justify-center bg-white rounded-2xl p-4 border border-slate-200">
                    <img src="{{ $qrDataUri }}" alt="QR Orden {{ $order->codigo_orden }}" class="w-36 h-36">
                    <span class="text-[10px] font-mono font-bold text-slate-800 mt-2">{{ $order->codigo_orden }}</span>
                </div>
            </div>

            <div class="border-t border-slate-800 pt-4 grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                <div class="space-y-1 text-slate-300">
                    <p><strong>Medio de Pago:</strong> {{ $order->etiquetaMetodoPago() }}</p>
                    @if($order->codigo_operacion)
                        <p><strong>Código Operación:</strong> <span class="font-mono text-white">{{ $order->codigo_operacion }}</span></p>
                    @endif
                    <p><strong>Entrega:</strong> {{ $order->tipo_entrega === 'delivery' ? 'Delivery: ' . $order->direccion_entrega : 'Recojo en Tienda' }}</p>
                </div>
                <div class="space-y-1 text-right">
                    <p class="text-slate-300">Subtotal: S/ {{ number_format((float) $order->subtotal, 2) }}</p>
                    @if((float) $order->descuento_karma > 0)
                        <p class="text-emerald-300 font-bold">Descuento Karma ({{ $order->puntos_karma_usados }} pts): − S/ {{ number_format((float) $order->descuento_karma, 2) }}</p>
                    @endif
                    <p class="text-lg font-black text-amber-400">Total: S/ {{ number_format((float) $order->total, 2) }}</p>
                </div>
            </div>

            @if(auth()->user()->esAdmin())
                <div class="no-print border-t border-slate-800 pt-4 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        @if($order->estado_pago === 'pendiente')
                            <form action="{{ route('orders.confirmPayment', $order) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-black px-4 py-2 rounded-xl">✓ Confirmar Pago</button>
                            </form>
                        @endif

                        @if($order->estado_pedido !== 'cancelado')
                            <form action="{{ route('orders.updateStatus', $order) }}" method="POST" class="flex items-center gap-1.5">
                                @csrf
                                @method('PATCH')
                                <select name="estado_pedido" class="rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-xs text-white">
                                    <option value="pendiente" @selected($order->estado_pedido === 'pendiente')>Pendiente</option>
                                    <option value="confirmado" @selected($order->estado_pedido === 'confirmado')>Confirmado</option>
                                    <option value="entregado" @selected($order->estado_pedido === 'entregado')>Entregado</option>
                                </select>
                                <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white text-xs font-bold px-3 py-2 rounded-xl">Actualizar Estado</button>
                            </form>
                        @endif
                    </div>

                    @if($order->estado_pedido !== 'cancelado')
                        <form action="{{ route('orders.cancel', $order) }}" method="POST" data-confirm="¿Anular orden {{ $order->codigo_orden }} y devolver stock/puntos?">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="bg-rose-950 hover:bg-rose-800 text-rose-200 border border-rose-500/40 text-xs font-bold px-4 py-2 rounded-xl">Anular Orden</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
