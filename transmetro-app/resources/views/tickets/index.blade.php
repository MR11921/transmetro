@extends('layouts.app')
@section('title', 'Tickets')
@section('content')
<div class="page-head">
    <div><h1>Tickets</h1><p>RF-011 · Emisión, estado y uso</p></div>
    @if(auth()->user()->puede('tat'))<a class="btn" href="{{ route('tat') }}">Abrir TAT</a>@endif
</div>
<div class="card">
    <form class="filters" method="GET">
        <div class="field"><label for="q">Código</label><input id="q" class="input" name="q" value="{{ request('q') }}"></div>
        <div class="field"><label for="estado">Estado</label>
            <select id="estado" name="estado" class="input"><option value="">Todos</option>@foreach(['VALIDO','UTILIZADO','ANULADO','VENCIDO'] as $e)<option @selected(request('estado') === $e)>{{ $e }}</option>@endforeach</select>
        </div>
        <button class="btn btn-ghost">Filtrar</button>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Código</th><th>Emisión</th><th>Método</th><th>Pasajero</th><th class="num">Valor</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($tickets as $t)
            <tr>
                <td><strong>{{ $t->codigo_ticket }}</strong></td>
                <td>{{ $t->fecha_emision->format('d/m/Y H:i') }}</td>
                <td>{{ $t->metodoPago->nombre }}</td>
                <td>{{ $t->pasajero ? $t->pasajero->nombre.' '.$t->pasajero->apellido : 'Anónimo' }}</td>
                <td class="num">Q{{ number_format($t->valor, 2) }}</td>
                <td><x-estado :valor="$t->estado" /></td>
                <td class="num">@if($t->estado === 'VALIDO')<form method="POST" action="{{ route('tickets.anular', $t) }}" onsubmit="return confirm('¿Anular el ticket {{ $t->codigo_ticket }}?')">@csrf @method('PATCH')<button class="btn btn-ghost btn-sm">Anular</button></form>@endif</td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">No hay tickets.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $tickets->links('partials.paginacion') }}
</div>
@endsection
