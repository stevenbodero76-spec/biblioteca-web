@extends('layouts.app')

@section('title', (auth()->user()->esCliente() ? 'Mis Tickets QR y Reservas' : 'Reservas, Pagos y Devoluciones') . ' · NOVAETHER™')

@section('content')
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
    <div>
        <h1 class="text-2xl font-extrabold text-white">
            🔄 {{ auth()->user()->esCliente() ? 'Mis Tickets QR, Alquileres y Lista de Espera' : 'Control de Reservas, Caja y Devoluciones' }}
        </h1>
        <p class="text-sm text-slate-300">
            {{ auth()->user()->esCliente() ? 'Consulta tus fechas límite, estado de pago y tickets QR.' : 'Validación de cobros Yape/Plin/Mostrador, devoluciones y reposición automática.' }}
        </p>
    </div>
    <a href="{{ route('loans.create') }}" class="bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-4 py-2.5 rounded-xl shadow transition">
        🔥 Nuevo Alquiler / Préstamo
    </a>
</div>

@if(auth()->user()->esCliente() && $miPerfilReader)
<div class="mb-6 bg-gradient-to-r from-slate-900 via-slate-900 to-amber-950/30 rounded-2xl border border-amber-400/30 p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 shadow-xl">
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-amber-400/15 border border-amber-400/40 flex items-center justify-center text-2xl">🏆</div>
        <div>
            <span class="text-[11px] font-black uppercase tracking-wider text-amber-400">{{ $miPerfilReader->nivelKarma() }}</span>
            <h2 class="text-lg font-extrabold text-white">{{ $miPerfilReader->puntos_karma }} Puntos Nova acumulados</h2>
            <p class="text-xs text-slate-300">Gana +25 puntos por cada devolución puntual.</p>
        </div>
    </div>
    <a href="{{ route('profile.edit') }}" class="self-start sm:self-auto px-3.5 py-1.5 rounded-xl bg-slate-950 border border-slate-700 hover:border-amber-400 text-xs font-bold text-slate-200 transition">
        {{ $miPerfilReader->etiquetaPlan() }} · Perfil →
    </a>
</div>
@endif

@if(isset($misWaitlists) && $misWaitlists->isNotEmpty())
<div class="mb-6 bg-slate-900/95 rounded-2xl border border-amber-400/30 p-5 shadow-xl">
    <h2 class="text-sm font-extrabold uppercase tracking-wider text-amber-400 mb-3">🔔 Tus Obras en Lista de Espera ({{ $misWaitlists->count() }})</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach($misWaitlists as $wl)
            <div class="bg-slate-950 rounded-xl border border-slate-800 p-4 flex flex-col justify-between gap-3">
                <div>
                    <span class="inline-block text-[10px] font-black uppercase px-2 py-0.5 rounded {{ $wl->estado === 'notificado' ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300' }}">
                        {{ $wl->estado === 'notificado' ? '🟢 ¡Disponible Ahora!' : '⏳ En Cola de Espera' }}
                    </span>
                    <p class="font-bold text-sm text-white mt-1.5">{{ $wl->book->titulo }}</p>
                    <p class="text-xs text-slate-300">{{ $wl->book->category->icono }} {{ $wl->book->category->nombre }}</p>
                </div>
                <div class="flex items-center justify-between gap-2 pt-2 border-t border-slate-800">
                    @if($wl->book->estaDisponible())
                        <a href="{{ route('loans.create', ['book_id' => $wl->book_id]) }}" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-black px-3 py-1.5 rounded-lg">Reservar →</a>
                    @else
                        <a href="{{ route('books.show', $wl->book) }}" class="text-xs font-bold text-amber-400 hover:underline">Ver ficha →</a>
                    @endif
                    <form action="{{ route('waitlists.destroy', $wl) }}" method="POST" data-confirm="¿Salir de la lista de espera para «{{ $wl->book->titulo }}»?">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs font-bold text-rose-400 hover:text-rose-300">Cancelar</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif

<form method="GET" action="{{ route('loans.index') }}" class="bg-slate-900/95 rounded-2xl border border-slate-800 p-4 mb-6 shadow-xl">
    <div class="grid grid-cols-1 sm:grid-cols-5 gap-3">
        <div class="sm:col-span-2">
            <label class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">
                {{ auth()->user()->esCliente() ? 'Buscar por obra o código operación' : 'Buscar por obra, cliente, DNI u op.' }}
            </label>
            <input type="text" name="buscar" value="{{ $filtros['buscar'] ?? '' }}" placeholder="Ej. One Piece, Dune, YP-..."
                   class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3.5 py-2 text-sm text-white placeholder-slate-400">
        </div>
        <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">Estado</label>
            <select name="estado" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">Todos</option>
                <option value="prestado" @selected(($filtros['estado'] ?? '') === 'prestado')>🟡 En Curso</option>
                <option value="vencido" @selected(($filtros['estado'] ?? '') === 'vencido')>🔴 Vencido</option>
                <option value="devuelto" @selected(($filtros['estado'] ?? '') === 'devuelto')>🟢 Cerrado / Devuelto</option>
            </select>
        </div>
        <div>
            <label class="block text-[11px] font-extrabold uppercase text-slate-300 mb-1">Pago</label>
            <select name="estado_pago" class="w-full rounded-xl bg-slate-950 border border-slate-700 px-3 py-2 text-sm text-white">
                <option value="">Todos</option>
                <option value="pagado" @selected(($filtros['estado_pago'] ?? '') === 'pagado')>✓ Verificado</option>
                <option value="pendiente" @selected(($filtros['estado_pago'] ?? '') === 'pendiente')>⏳ Pendiente</option>
                <option value="anulado" @selected(($filtros['estado_pago'] ?? '') === 'anulado')>✕ Anulado</option>
            </select>
        </div>
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 bg-amber-400 hover:bg-amber-300 text-slate-950 text-xs font-black py-2.5 px-3 rounded-xl">🔍 Filtrar</button>
            <a href="{{ route('loans.index') }}" class="bg-slate-800 hover:bg-slate-700 text-slate-200 text-xs font-bold py-2.5 px-3 rounded-xl">Limpiar</a>
        </div>
    </div>
</form>

<div class="bg-slate-900/95 rounded-2xl border border-slate-800 shadow-2xl overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-left border-collapse text-sm">
            <thead>
                <tr class="bg-slate-950 border-b border-slate-800 text-xs uppercase text-slate-300">
                    <th class="py-3.5 px-4">Ticket</th>
                    <th class="py-3.5 px-4">Obra</th>
                    @if(auth()->user()->esAdmin())
                        <th class="py-3.5 px-4">Cliente</th>
                    @endif
                    <th class="py-3.5 px-4">Modalidad</th>
                    <th class="py-3.5 px-4">Fechas</th>
                    <th class="py-3.5 px-4">Estado</th>
                    <th class="py-3.5 px-4 text-right">Operaciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-800/80">
                @forelse($loans as $loan)
                    <tr class="hover:bg-slate-800/40">
                        <td class="py-3.5 px-4 font-mono text-xs text-amber-300 font-bold">#{{ $loan->id }}</td>
                        <td class="py-3.5 px-4">
                            <a href="{{ route('books.show', $loan->book) }}" class="font-bold text-white hover:text-amber-300">{{ $loan->book->titulo }}</a>
                            <p class="text-xs text-slate-300">{{ $loan->book->category->icono }} {{ $loan->book->category->nombre }}</p>
                        </td>
                        @if(auth()->user()->esAdmin())
                            <td class="py-3.5 px-4">
                                <p class="font-semibold text-slate-100">{{ $loan->reader->nombre_completo }}</p>
                                <p class="text-xs text-slate-300">Doc: {{ $loan->reader->dni_codigo }}</p>
                            </td>
                        @endif
                        <td class="py-3.5 px-4 text-xs">
                            <p class="font-semibold text-slate-200">{{ $loan->etiquetaMetodoPago() }}</p>
                            <span class="text-[10px] font-bold {{ $loan->estado_pago === 'pagado' ? 'text-emerald-300' : ($loan->estado_pago === 'anulado' ? 'text-slate-400' : 'text-amber-300') }}">
                                {{ $loan->etiquetaEstadoPago() }} {{ $loan->codigo_operacion ? '· ' . $loan->codigo_operacion : '' }}
                            </span>
                        </td>
                        <td class="py-3.5 px-4 text-xs text-slate-300">
                            <div><strong>Salida:</strong> {{ $loan->fecha_prestamo->format('d/m/Y') }}</div>
                            <div><strong>Límite:</strong> {{ $loan->fecha_devolucion_esperada->format('d/m/Y') }}</div>
                            @if($loan->fecha_devolucion_real)
                                <div class="text-emerald-400 font-bold"><strong>Devuelto:</strong> {{ $loan->fecha_devolucion_real->format('d/m/Y') }}</div>
                            @endif
                        </td>
                        <td class="py-3.5 px-4">
                            <x-status-badge :estado="$loan->estaVencido() ? 'vencido' : $loan->estado" :estadoPago="$loan->estado_pago" />
                        </td>
                        <td class="py-3.5 px-4 text-right">
                            <div class="inline-flex items-center gap-1.5 flex-wrap justify-end">
                                <a href="{{ route('loans.ticket', $loan) }}"
                                   class="bg-slate-800 hover:bg-slate-700 border border-slate-700 text-amber-300 text-xs font-extrabold px-2.5 py-1.5 rounded-lg transition">
                                    🎫 QR
                                </a>

                                @if(auth()->user()->esAdmin() && $loan->estado !== 'devuelto' && ! $loan->estaAnulado())
                                    <form action="{{ route('loans.devolver', $loan) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="bg-emerald-500 hover:bg-emerald-400 text-slate-950 text-xs font-extrabold px-2.5 py-1.5 rounded-lg shadow transition">✅ Devolver</button>
                                    </form>
                                @endif

                                @if(auth()->user()->esAdmin())
                                    <form action="{{ route('loans.destroy', $loan) }}" method="POST" class="inline"
                                          data-confirm="¿Eliminar permanentemente el registro de préstamo #{{ $loan->id }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-rose-400 hover:text-rose-300 px-1.5 py-1" title="Eliminar">🗑️</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-slate-300">No se encontraron reservas o préstamos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">{{ $loans->links() }}</div>
@endsection
