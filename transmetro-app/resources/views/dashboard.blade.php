@extends('layouts.app')
@section('title', 'Panel general')
@section('content')
@php
    $u = auth()->user();
    $hora = now()->hour;
    $saludo = $hora < 12 ? 'Buenos días' : ($hora < 19 ? 'Buenas tardes' : 'Buenas noches');
    // Acciones frecuentes; cada rol ve solo las que puede hacer (RN-015)
    $acciones = collect([
        ['Vender ticket', 'tat', 'tat', 'ticket'],
        ['Validar acceso', 'acceso.index', 'control_acceso', 'entrada'],
        ['Ocupación y TCP', 'ocupacion.index', 'ocupacion', 'personas'],
        ['Programar recorrido', 'recorridos.create', 'recorridos', 'ruta'],
        ['Registrar incidente', 'incidentes.create', 'incidentes', 'incidente'],
        ['Ver reportes', 'reportes.index', 'reportes', 'grafica'],
    ])->filter(fn ($a) => $u->puede($a[2]))->take(4);
    $modulos = collect(config('transmetro.modulos'))
        ->map(fn ($m) => [...$m, 'opciones' => array_values(array_filter($m['opciones'], fn ($o) => $u->puede($o[3])))])
        ->filter(fn ($m) => $m['opciones']);
    // [etiqueta, valor, ícono, ruta, parámetros, permiso, estado]
    $indicadores = [
        ['Líneas activas', $kpi['lineas'], 'linea', 'lineas.index', [], 'lineas', ''],
        ['Estaciones', $kpi['estaciones'], 'estacion', 'catalogos.index', ['estaciones'], 'estaciones', ''],
        ['Buses activos', $kpi['buses'], 'bus', 'buses.index', [], 'buses', ''],
        ['Recorridos en curso', $kpi['recorridos'], 'ruta', 'ocupacion.index', [], 'ocupacion', ''],
        ['Tickets hoy', $kpi['tickets_hoy'], 'ticket', 'tickets.index', [], 'tickets', ''],
        ['Entradas hoy', $kpi['entradas_hoy'], 'entrada', 'acceso.index', [], 'control_acceso', ''],
        ['Alertas pendientes', $kpi['alertas'], 'alerta', 'alertas.index', [], 'alertas', $kpi['alertas'] ? 'bad' : ''],
        ['Incidentes abiertos', $kpi['incidentes'], 'incidente', 'incidentes.index', [], 'incidentes', $kpi['incidentes'] ? 'warn' : ''],
        ['Dispositivos con falla', $kpi['dispositivos_falla'], 'dispositivo', 'catalogos.index', ['dispositivos'], 'dispositivos', $kpi['dispositivos_falla'] ? 'warn' : ''],
    ];
@endphp

<section class="hero">
    <div>
        <p class="hero-date"><x-icono nombre="reloj" :tam="14" /> {{ ucfirst(now()->translatedFormat('l j \d\e F, H:i')) }}</p>
        <h1>{{ $saludo }}, {{ $u->nombre }}</h1>
        <p>Este es el estado del servicio de Transmetro. Elija una acción rápida o entre a un módulo.</p>
    </div>
    @if ($acciones->isNotEmpty())
        <div class="quick">
            @foreach ($acciones as [$label, $ruta, , $icono])
                <a class="quick-btn" href="{{ route($ruta) }}"><x-icono :nombre="$icono" :tam="22" /><span>{{ $label }}</span></a>
            @endforeach
        </div>
    @endif
</section>

@if ($kpi['alertas'] || $kpi['incidentes'] || $kpi['dispositivos_falla'])
    <div class="attention" role="status">
        <strong>Requiere atención:</strong>
        @if ($kpi['alertas'])<span class="badge bad">{{ $kpi['alertas'] }} {{ $kpi['alertas'] === 1 ? 'alerta pendiente' : 'alertas pendientes' }}</span>@endif
        @if ($kpi['incidentes'])<span class="badge warn">{{ $kpi['incidentes'] }} {{ $kpi['incidentes'] === 1 ? 'incidente abierto' : 'incidentes abiertos' }}</span>@endif
        @if ($kpi['dispositivos_falla'])<span class="badge warn">{{ $kpi['dispositivos_falla'] }} {{ $kpi['dispositivos_falla'] === 1 ? 'dispositivo con falla' : 'dispositivos con falla' }}</span>@endif
    </div>
@endif

<div class="kpis">
    @foreach ($indicadores as [$label, $valor, $icono, $ruta, $params, $permiso, $estado])
        @php $enlace = $u->puede($permiso); @endphp
        <{{ $enlace ? 'a' : 'div' }} class="card kpi kpi-icon {{ $estado }}" @if($enlace) href="{{ route($ruta, $params) }}" @endif>
            <span class="kpi-ico"><x-icono :nombre="$icono" :tam="20" /></span>
            <div><div class="label">{{ $label }}</div><div class="value">{{ $valor }}</div></div>
        </{{ $enlace ? 'a' : 'div' }}>
    @endforeach
</div>

<div class="grid grid-2">
    <section class="card">
        <div class="card-head"><h2>Buses en ruta</h2>@if($u->puede('ocupacion'))<a class="btn btn-ghost btn-sm" href="{{ route('ocupacion.index') }}">Ver ocupación</a>@endif</div>
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
        <div class="card-head"><h2>Alertas pendientes</h2>@if($u->puede('alertas'))<a class="btn btn-ghost btn-sm" href="{{ route('alertas.index') }}">Gestionar</a>@endif</div>
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

<section class="mt-24">
    <div class="section-head"><h2>Módulos del sistema</h2><small>Solo se muestran los módulos de su rol</small></div>
    <div class="modulos">
    @foreach ($modulos as $m)
        <article class="card modulo">
            <div class="modulo-head">
                <span class="modulo-ico"><x-icono :nombre="$m['icono']" :tam="24" /></span>
                <div><span class="modulo-num">Módulo {{ $m['numero'] }}</span><h3>{{ $m['nombre'] }}</h3></div>
            </div>
            <p class="muted">{{ $m['descripcion'] }}</p>
            <ul class="modulo-links">
                @foreach ($m['opciones'] as [$label, $ruta, $params])
                    <li><a href="{{ route($ruta, $params) }}">{{ $label }} <x-icono nombre="flecha" :tam="14" /></a></li>
                @endforeach
            </ul>
        </article>
    @endforeach
    </div>
</section>

<section class="card mt-24">
    <div class="card-head"><h2>Líneas</h2>@if($u->puede('lineas'))<a class="btn btn-ghost btn-sm" href="{{ route('distancias.index') }}">Ver distancias</a>@endif</div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Línea</th><th class="num">Estaciones</th><th class="num">Distancia</th><th class="num">Buses</th><th>Buses requeridos (mín–máx)</th></tr></thead>
        <tbody>
        @forelse ($lineas as $l)
            <tr>
                <td>@if($u->puede('lineas'))<a href="{{ route('lineas.show', $l) }}">{{ $l->nombre }}</a>@else{{ $l->nombre }}@endif</td>
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
