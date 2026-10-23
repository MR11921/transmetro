@extends('layouts.app')
@section('title', $titulo)
@section('content')
<div class="page-head">
    <div><h1>{{ $titulo }}</h1><p>{{ count($filas) }} {{ count($filas) === 1 ? 'registro' : 'registros' }}</p></div>
    <div class="actions">
        <a class="btn btn-ghost" href="{{ route('reportes.index') }}">Todos los reportes</a>
        <a class="btn" href="{{ request()->fullUrlWithQuery(['formato' => 'pdf']) }}">Exportar PDF</a>
    </div>
</div>
<div class="card">
    <form class="filters" method="GET">
        <div class="field"><label for="desde">Desde</label><input id="desde" type="date" class="input" name="desde" value="{{ $desde->toDateString() }}"></div>
        <div class="field"><label for="hasta">Hasta</label><input id="hasta" type="date" class="input" name="hasta" value="{{ $hasta->toDateString() }}"></div>
        <button class="btn btn-ghost">Aplicar</button>
        <small>El rango aplica a reportes con fecha (recorridos, tickets, movimientos, alertas, incidentes).</small>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr>@foreach ($columnas as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
        <tbody>
        @forelse ($filas as $fila)
            <tr>@foreach ($fila as $v)<td>@if(in_array($v, ['Cumple','No cumple','SIN PARQUEO','ACTIVO','ACTIVA','INACTIVO','INACTIVA','MANTENIMIENTO','PENDIENTE','ATENDIDA','CANCELADA','ABIERTO','EN_PROCESO','CERRADO','EN_CURSO','FINALIZADO','CANCELADO','PROGRAMADO','ALTA','MEDIA','BAJA','CRITICA'], true))<x-estado :valor="$v" />@else{{ $v }}@endif</td>@endforeach</tr>
        @empty
            <tr><td colspan="{{ count($columnas) }}" class="empty">Sin datos para el período.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</div>
@endsection
