@extends('layouts.app')

@section('title', 'Ticket QR ' . $loan->codigoTicket() . ' · NOVAETHER™')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="no-print flex items-center justify-between mb-6">
        <a href="{{ route('loans.index') }}" class="text-xs font-extrabold uppercase text-amber-400 hover:underline">
            ← Volver a {{ auth()->user()->esCliente() ? 'Mis Tickets QR' : 'Préstamos' }}
        </a>
        <button type="button" onclick="window.print()"
                class="bg-gradient-to-r from-amber-400 to-orange-500 hover:from-amber-300 hover:to-orange-400 text-slate-950 text-xs font-black px-5 py-2.5 rounded-xl shadow-lg transition">
            🖨️ Imprimir / Guardar PDF
        </button>
    </div>

    @if($loan->pagoPendiente())
        <div class="mb-4 rounded-2xl bg-amber-950/60 border border-amber-400/50 p-4 text-xs text-amber-100">
            ⏳ <strong class="text-amber-300">PRÉSTAMO PENDIENTE DE ENTREGA:</strong> Este ejemplar quedó separado para ti. Presenta este ticket QR en biblioteca para retirar tu libro.
        </div>
    @elseif($loan->estaAnulado())
        <div class="mb-4 rounded-2xl bg-rose-950/60 border border-rose-500/50 p-4 text-xs text-rose-100">
            ⛔ <strong>TICKET ANULADO / EXPIRADO:</strong> Esta reserva fue cancelada y su stock regresó al catálogo.
        </div>
    @endif

    <div class="print-card bg-slate-900 border border-slate-700 rounded-3xl overflow-hidden shadow-2xl">
        <div class="bg-slate-950 px-6 sm:px-8 py-6 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <p class="text-xs font-black uppercase tracking-widest text-amber-400">NOVAETHER™ · Comprobante Oficial</p>
                <h1 class="text-2xl font-extrabold text-white mt-0.5">Ticket de Préstamo Digital</h1>
                <p class="text-xs font-mono text-slate-300 mt-1">Código Verificado: <strong class="text-amber-300">{{ $loan->codigoTicket() }}</strong></p>
            </div>
            <div class="text-right space-y-1">
                <x-status-badge :estado="$loan->estaVencido() ? 'vencido' : $loan->estado" :estadoPago="$loan->estado_pago" />
                <span class="block text-[11px] font-bold {{ $loan->estado_pago === 'pagado' ? 'text-emerald-300' : ($loan->estado_pago === 'anulado' ? 'text-rose-300' : 'text-amber-300') }}">
                    {{ $loan->etiquetaEstadoPago() }}
                </span>
            </div>
        </div>

        <div class="p-6 sm:p-8 grid grid-cols-1 sm:grid-cols-12 gap-6 items-center">
            <div class="sm:col-span-8 space-y-4">
                <div>
                    <span class="text-[11px] font-extrabold uppercase text-slate-400">Obra Solicitada</span>
                    <h2 class="font-serif-editorial text-xl font-bold text-white">{{ $loan->book->titulo }}</h2>
                    <p class="text-xs text-slate-300">{{ $loan->book->autor }} · ISBN: {{ $loan->book->isbn }}</p>
                    <p class="text-xs text-amber-300 mt-1">Ubicación: {{ $loan->book->ubicacion_estante ?? 'Catálogo General' }}</p>
                </div>

                <div class="grid grid-cols-2 gap-3 pt-3 border-t border-slate-800">
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase text-slate-400">Titular / Lector</span>
                        <span class="text-xs font-bold text-white">{{ $loan->reader->nombre_completo }}</span>
                        <span class="block text-[11px] text-slate-300">{{ $loan->reader->dni_codigo ? 'Doc: ' . $loan->reader->dni_codigo : 'Lector Público' }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase text-slate-400">Modalidad</span>
                        <span class="text-xs font-bold text-white">{{ $loan->etiquetaMetodoPago() }}</span>
                        @if($loan->codigo_operacion)
                            <span class="block text-[10px] font-mono text-slate-300">Op: {{ $loan->codigo_operacion }}</span>
                        @endif
                    </div>
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase text-slate-400">Fecha de Salida</span>
                        <span class="text-xs font-bold text-white">{{ $loan->fecha_prestamo->format('d/m/Y') }}</span>
                    </div>
                    <div>
                        <span class="block text-[10px] font-extrabold uppercase text-slate-400">Fecha Límite Devolución</span>
                        <span class="text-xs font-bold text-amber-300">{{ $loan->fecha_devolucion_esperada->format('d/m/Y') }}</span>
                    </div>
                </div>

                @if($loan->observaciones)
                    <div class="bg-slate-950 rounded-xl p-3 border border-slate-800 text-xs text-slate-300">
                        <strong class="text-slate-200">Notas:</strong> {{ $loan->observaciones }}
                    </div>
                @endif
            </div>

            <div class="sm:col-span-4 flex flex-col items-center justify-center bg-white rounded-2xl p-4 border border-slate-200">
                <img src="{{ $qrDataUri }}" alt="Código QR de verificación {{ $loan->codigoTicket() }}" class="w-40 h-40">
                <span class="text-[10px] font-mono font-bold text-slate-800 mt-2 text-center">{{ $loan->codigoTicket() }}</span>
                <span class="text-[10px] text-slate-600 text-center">Presenta este QR al retirar tu libro</span>
            </div>
        </div>
    </div>
</div>
@endsection
