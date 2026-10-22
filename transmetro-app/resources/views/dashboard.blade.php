@extends('layouts.app')
@section('title', 'Panel general')
@section('content')
<div class="page-head">
    <div><h1>Panel general</h1><p>Estado del servicio al {{ now()->format('d/m/Y H:i') }}</p></div>
</div>

<div class="kpis">
    <div class="card kpi"><div class="label">Líneas activas</div><div class="value">{{ $kpi['lineas'] }}</div></div>
    <div class="card kpi"><div class="label">Estaciones</div><div class="value">{{ $kpi['estaciones'] }}</div></div>
    <div class="card kpi"><div class="label">Buses activos</div><div class="value">{{ $kpi['buses'] }}</div></div>
    <div class="card kpi"><div class="label">Recorridos en curso</div><div class="value">{{ $kpi['recorridos'] }}</div></div>
    <div class="card kpi {{ $kpi['alertas'] ? 'bad' : '' }}"><div class="label">Alertas pendientes</div><div class="value">{{ $kpi['alertas'] }}</div></div>
    <div class="card kpi {{ $kpi['incidentes'] ? 'warn' : '' }}"><div class="label">Incidentes abiertos</div><div class="value">{{ $kpi['incidentes'] }}</div></div>
    <div class="card kpi"><div class="label">Tickets hoy</div><div class="value">{{ $kpi['tickets_hoy'] }}</div></div>
    <div class="card kpi"><div class="label">Entradas hoy</div><div class="value">{{ $kpi['entradas_hoy'] }}</div></div>
    <div class="card kpi {{ $kpi['dispositivos_falla'] ? 'warn' : '' }}"><div class="label">Dispositivos con falla</div><div class="value">{{ $kpi['dispositivos_falla'] }}</div></div>
</div>

<div class="grid grid-2">
    <section class="card">
        <div class="card-head"><h2>Buses en ruta</h2>@if(auth()->user()->puede('tcp'))<a class="btn btn-ghost btn-sm" href="{{ route('recorridos.index') }}">Ver recorridos</a>@endif</div>
        @forelse ($enCurso as $r)
            @php $pct = $r->porcentajeOcupacion(); @endphp
            <div class="mt-16">
                <div class="card-head" style="margin-bottom:4px">
                    <span><strong>Bus {{ $r->bus->numero_interno }}</strong> <span class="muted">· {{ $r->linea->nombre }} · {{ $r->piloto->nombre_completo }}</span></span>
                    <span>{{ $r->ocupacionActual() }}/{{ $r->bus->capacidad_maxima }} ({{ $pct }} %)</span>
                </div>
                <div class="meter {{ $pct >= 90 ? 'bad' : ($pct < 25 ? 'warn' : '') }}"><span style="width: {{ min($pct, 100) }}%"></span></div>
                <small>Próxima estación: {{ $r->siguienteEstacion()?->nombre ?? 'Fin de línea' }}</small>
            </div>
        @empty
            <p class="empty">No hay recorridos en curso.</p>
        @endforelse
    </section>

    <section class="card">
        <div class="card-head"><h2>Alertas pendientes</h2>@if(auth()->user()->puede('alertas'))<a class="btn btn-ghost btn-sm" href="{{ route('alertas.index') }}">Gestionar</a>@endif</div>
        <div class="table-wrap"><table class="table">
            <tbody>
            @forelse ($alertas as $a)
                <tr>
                    <td><x-estado :valor="$a->nivel" /></td>
                    <td><strong>{{ str_replace('_', ' ', $a->tipo_alerta) }}</strong><br><small>{{ $a->descripcion }}</small></td>
                    <td class="num"><small>{{ $a->fecha_hora->diffForHumans() }}</small></td>
                </tr>
            @empty
                <tr><td class="empty">Sin alertas pendientes.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</div>

<section class="card mt-16">
    <div class="card-head"><h2>Líneas</h2></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Línea</th><th class="num">Estaciones</th><th class="num">Distancia</th><th class="num">Buses</th><th>RN-004 (mín–máx)</th></tr></thead>
        <tbody>
        @forelse ($lineas as $l)
            <tr>
                <td>@if(auth()->user()->puede('lineas'))<a href="{{ route('lineas.show', $l) }}">{{ $l->nombre }}</a>@else{{ $l->nombre }}@endif</td>
                <td class="num">{{ $l->estaciones->count() }}</td>
                <td class="num">{{ number_format($l->distanciaTotal(), 2) }} km</td>
                <td class="num">{{ $l->buses_count }}</td>
                <td><x-estado :valor="$l->cumpleCantidadBuses() ? 'Cumple' : 'No cumple'" /> <small>{{ $l->estaciones->count() }}–{{ $l->estaciones->count() * 2 }}</small></td>
            </tr>
        @empty
            <tr><td colspan="5" class="empty">No hay líneas registradas.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
