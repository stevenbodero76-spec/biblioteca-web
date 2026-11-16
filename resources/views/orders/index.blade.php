@extends('layouts.app')

@section('title', (auth()->user()->esAdmin() ? 'Órdenes y Caja' : 'Mis Pedidos') . ' · NOVAETHER™')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-white">📦 {{ auth()->user()->esAdmin() ? 'Gestión de Órdenes y Compras' : 'Mis Pedidos y Comprobantes' }}</h1>
        <p class="text-sm text-slate-300">Compras directas, órdenes mixtas y seguimiento logístico.</p>
    </div>
    <a href="{{ route('books.index') }}" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-white text-xs font-bold px-4 py-2.5 rounded-xl">
        📚 Volver al Catálogo
    </a>
</div>

<form method="GET" action="{{ route('orders.index') }}" class="bg-slate-900/95 rounded-2xl border border-slate-800 p-4 mb-6 shadow-xl">
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">Buscar orden / operación</label>
            <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="ORD-2026..."
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white">
        </div>
        <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">Estado de Pago</label>
            <select name="estado_pago" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">Todos</option>
                <option value="pendiente" @selected(($filtros['estado_pago'] ?? '') === 'pendiente')>Pendiente</option>
                <option value="pagado" @selected(($filtros['estado_pago'] ?? '') === 'pagado')>Pagado</option>
                <option value="anulado" @selected(($filtros['estado_pago'] ?? '') === 'anulado')>Anulado</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">Estado Logístico</label>
            <select name="estado_pedido" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">Todos</option>
                <option value="pendiente" @selected(($filtros['estado_pedido'] ?? '') === 'pendiente')>Pendiente</option>
                <option value="confirmado" @selected(($filtros['estado_pedido'] ?? '') === 'confirmado')>Confirmado</option>
                <option value="entregado" @selected(($filtros['estado_pedido'] ?? '') === 'entregado')>Entregado</option>
                <option value="cancelado" @selected(($filtros['estado_pedido'] ?? '') === 'cancelado')>Cancelado</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black py-2.5 px-3 rounded-xl">🔍 Filtrar</button>
            <a href="{{ route('orders.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold py-2.5 px-3 rounded-xl">Limpiar</a>
        </div>
    </div>
</form>

<div class="bg-slate-900/95 rounded-2xl border border-slate-800 shadow-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-950 border-b border-slate-800 text-xs uppercase text-slate-300">
                    <th class="py-3.5 px-4">Orden</th>
                    @if(auth()->user()->esAdmin())
                        <th class="py-3.5 px-4">Cliente</th>
                    @endif
                    <th class="py-3.5 px-4">Detalle</th>
                    <th class="py-3.5 px-4">Pago / Total</th>
                    <th class="py-3.5 px-4">Estado</th>
                    <th class="py-3.5 px-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80">
                @forelse($orders as $order)
                    <tr class="hover:bg-slate-800/40">
                        <td class="py-3.5 px-4">
                            <span class="font-mono font-bold text-amber-300 text-xs">{{ $order->codigo_orden }}</span>
                            <p class="text-[11px] text-slate-400">{{ $order->created_at->format('d/m/Y H:i') }}</p>
                        </td>
                        @if(auth()->user()->esAdmin())
                            <td class="py-3.5 px-4 text-xs">
                                <p class="font-bold text-white">{{ $order->user->name }}</p>
                                <p class="text-slate-400">{{ $order->user->email }}</p>
                            </td>
                        @endif
                        <td class="py-3.5 px-4 text-xs text-slate-200">
                            {{ $order->items->count() }} ítem(s) · {{ $order->tipo_entrega === 'delivery' ? '🛵 Delivery' : '🏪 Tienda QR' }}
                        </td>
                        <td class="py-3.5 px-4 text-xs">
                            <p class="font-black text-amber-300">S/ {{ number_format((float) $order->total, 2) }}</p>
                            <p class="text-slate-300">{{ $order->etiquetaMetodoPago() }}</p>
                        </td>
                        <td class="py-3.5 px-4 text-xs space-y-1">
                            <span class="inline-block px-2 py-0.5 rounded-full font-bold {{ $order->estado_pago === 'pagado' ? 'bg-emerald-500/20 text-emerald-300' : ($order->estado_pago === 'anulado' ? 'bg-rose-500/20 text-rose-200' : 'bg-amber-500/20 text-amber-300') }}">
                                {{ $order->etiquetaEstadoPago() }}
                            </span>
                            <span class="block text-[11px] text-slate-300">Pedido: {{ $order->etiquetaEstadoPedido() }}</span>
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <div class="inline-flex items-center gap-1.5">
                                <a href="{{ route('orders.show', $order) }}" class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-amber-300 text-xs font-bold px-3 py-1.5 rounded-lg">
                                    Ver Detalle / QR
                                </a>
                                @if(auth()->user()->esAdmin() && $order->estado_pago === 'pendiente')
                                    <form action="{{ route('orders.confirmPayment', $order) }}" method="POST">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-black px-2.5 py-1.5 rounded-lg">✓ Cobrar</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-8 text-center text-slate-300">Aún no registras pedidos en la tienda.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $orders->links() }}</div>
@endsection
