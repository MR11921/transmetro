@extends('layouts.app')
@section('title', 'Líneas')
@section('content')
<div class="page-head">
    <div><h1>Líneas</h1><p>RF-004 · Líneas, recorridos y distancias</p></div>
    <a class="btn" href="{{ route('lineas.create') }}">Nueva línea</a>
</div>
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>Línea</th><th>Municipalidad</th><th>Recorrido</th><th class="num">Distancia</th><th class="num">Buses</th><th>RN-004</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse ($lineas as $l)
        <tr>
            <td><strong>{{ $l->nombre }}</strong></td>
            <td>{{ $l->municipalidad->nombre }}</td>
            <td><small>{{ $l->estaciones->pluck('nombre')->join(' → ') ?: 'Sin estaciones' }}</small></td>
            <td class="num">{{ number_format($l->distanciaTotal(), 2) }} km</td>
            <td class="num">{{ $l->buses_count }}</td>
            <td><x-estado :valor="$l->cumpleCantidadBuses() ? 'Cumple' : 'No cumple'" /></td>
            <td><x-estado :valor="$l->estado" /></td>
            <td class="num"><a class="btn btn-ghost btn-sm" href="{{ route('lineas.show', $l) }}">Abrir</a></td>
        </tr>
    @empty
        <tr><td colspan="8" class="empty">No hay líneas registradas.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
@endsection
