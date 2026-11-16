@props(['estado', 'estadoPago' => null])

@if($estadoPago === 'anulado')
    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700">Anulado</span>
@elseif($estado === 'devuelto')
    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Devuelto</span>
@elseif($estado === 'vencido')
    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-500/20 text-rose-200 border border-rose-500/40">Vencido</span>
@elseif($estadoPago === 'pendiente')
    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-200 border border-amber-400/50">Pago Pendiente</span>
@else
    <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-sky-500/20 text-sky-300 border border-sky-500/30">En Curso</span>
@endif
