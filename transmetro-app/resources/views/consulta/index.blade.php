@extends('layouts.app')
@section('title', 'Consulta integral')
@section('content')
<div class="page-head">
    <div><h1>Consulta integral</h1><p>Toda la información de una línea en un solo lugar</p></div>
    <form method="GET" class="filters" style="margin:0">
        <div class="field"><label for="linea">Línea</label>
            <select class="input" id="linea" name="linea" onchange="this.form.submit()">
                @foreach ($lineas as $l)<option value="{{ $l->id_linea }}" @selected($linea?->id_linea === $l->id_linea)>{{ $l->nombre }}</option>@endforeach
            </select>
        </div>
        <noscript><button class="btn btn-sm">Consultar</button></noscript>
    </form>
</div>

@if (! $linea)
    <p class="card empty">No hay líneas registradas.</p>
@else
<div class="kpis">
    <div class="card kpi"><div class="label">Estaciones</div><div class="value">{{ $linea->estaciones->count() }}</div></div>
    <div class="card kpi"><div class="label">Accesos</div><div class="value">{{ $linea->estaciones->sum(fn ($e) => $e->accesos->count()) }}</div></div>
    <div class="card kpi"><div class="label">Buses asignados</div><div class="value">{{ $linea->buses->count() }}</div></div>
    <div class="card kpi"><div class="label">En ruta</div><div class="value">{{ $enCurso->count() }}</div></div>
    <div class="card kpi"><div class="label">Distancia</div><div class="value">{{ number_format($linea->distanciaTotal(), 1) }}<small> km</small></div></div>
    <div class="card kpi {{ $alertas->count() ? 'bad' : '' }}"><div class="label">Alertas pendientes</div><div class="value">{{ $alertas->count() }}</div></div>
</div>

<section class="card">
    <div class="card-head"><h2>Estaciones y accesos</h2><small>{{ $linea->municipalidad->nombre }}</small></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>#</th><th>Estación</th><th>Accesos y guardias</th><th>Dispositivos</th><th>Estado</th></tr></thead>
        <tbody>
        @foreach ($linea->estaciones as $i => $e)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $e->nombre }}</strong></td>
                <td>
                    @forelse ($e->accesos as $a)
                        <div>{{ $a->nombre }}
                            @if ($a->guardias->isEmpty())<span class="badge bad">Sin guardia</span>@else<small>· {{ $a->guardias->pluck('nombre_completo')->join(', ') }}</small>@endif
                        </div>
                    @empty
                        <small class="muted">Sin accesos</small>
                    @endforelse
                </td>
                <td>
                    @php $fallas = $e->dispositivos->whereIn('estado', ['DESCONECTADO', 'MANTENIMIENTO'])->count(); @endphp
                    {{ $e->dispositivos->count() }} @if ($fallas)<span class="badge warn">{{ $fallas }} con falla</span>@endif
                </td>
                <td><x-estado :valor="$e->estado" /></td>
            </tr>
        @endforeach
        </tbody>
    </table></div>
</section>

<div class="grid grid-2 mt-16">
    <section class="card">
        <div class="card-head"><h2>Buses en ruta</h2></div>
        @forelse ($enCurso as $r)
            @php $pct = $r->porcentajeOcupacion(); @endphp
            <div class="mt-16">
                <div class="card-head" style="margin-bottom:4px">
                    <span><strong>Bus {{ $r->bus->numero_interno }}</strong> <span class="muted">· {{ $r->piloto->nombre_completo }}</span></span>
                    <span>{{ $r->ocupacionActual() }}/{{ $r->bus->capacidad_maxima }} ({{ $pct }} %)</span>
                </div>
                <div class="meter {{ $pct >= 90 ? 'bad' : ($pct < 25 ? 'warn' : '') }}"><span style="width: {{ min($pct, 100) }}%"></span></div>
                <small>Próxima estación: {{ $r->siguienteEstacion()?->nombre ?? 'Fin de línea' }}</small>
            </div>
        @empty
            <p class="empty">Ningún bus de esta línea está en ruta.</p>
        @endforelse
        @if ($linea->buses->isNotEmpty())
            <p class="mt-16"><small>Asignados: {{ $linea->buses->pluck('numero_interno')->join(', ') }}</small></p>
        @endif
    </section>

    <section class="card">
        <div class="card-head"><h2>Alertas pendientes</h2></div>
        <div class="table-wrap"><table class="table">
            <tbody>
            @forelse ($alertas as $a)
                <tr>
                    <td><x-estado :valor="$a->nivel" /></td>
                    <td><strong>{{ str_replace('_', ' ', $a->tipo_alerta) }}</strong><br><small>{{ $a->descripcion }}</small></td>
                    <td class="num"><small>{{ $a->fecha_hora->diffForHumans() }}</small></td>
                </tr>
            @empty
                <tr><td class="empty">Sin alertas pendientes en esta línea.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</div>
@endif
@endsection
