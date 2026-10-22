@extends('layouts.app')
@section('title', 'Buses')
@section('content')
<div class="page-head">
    <div><h1>Buses</h1><p>RF-007 · RN-005 una línea por bus · RN-006 parqueo obligatorio</p></div>
    <a class="btn" href="{{ route('buses.create') }}">Nuevo bus</a>
</div>
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>Nº interno</th><th>Placa</th><th class="num">Capacidad</th><th>Línea actual</th><th>Parqueo actual</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse ($buses as $b)
        @php $linea = $b->lineaActual(); $parqueo = $b->parqueoActual(); @endphp
        <tr>
            <td><strong>{{ $b->numero_interno }}</strong></td>
            <td>{{ $b->placa }}</td>
            <td class="num">{{ $b->capacidad_maxima }}</td>
            <td>{{ $linea?->nombre ?? '—' }}</td>
            <td>@if($parqueo){{ $parqueo->codigo }}@else<span class="badge bad">Sin parqueo</span>@endif</td>
            <td><x-estado :valor="$b->estado" /></td>
            <td class="num"><a class="btn btn-ghost btn-sm" href="{{ route('buses.show', $b) }}">Abrir</a></td>
        </tr>
    @empty
        <tr><td colspan="7" class="empty">No hay buses registrados.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
@endsection
