@extends('layouts.app')
@section('title', $linea->nombre)
@section('content')
@php $nEst = $linea->estaciones->count(); $nBus = $linea->buses->count(); @endphp
<div class="page-head">
    <div>
        <h1>{{ $linea->nombre }} <x-estado :valor="$linea->estado" /></h1>
        <p>{{ $linea->municipalidad->nombre }} · {{ $linea->descripcion }} · Consulta integral (RF-025)</p>
    </div>
    <div class="actions"><a class="btn btn-ghost" href="{{ route('lineas.edit', $linea) }}">Editar línea</a></div>
</div>

<div class="kpis">
    <div class="card kpi"><div class="label">Estaciones</div><div class="value">{{ $nEst }}</div></div>
    <div class="card kpi"><div class="label">Distancia total</div><div class="value">{{ number_format($linea->distanciaTotal(), 2) }} <small>km</small></div></div>
    <div class="card kpi {{ $linea->cumpleCantidadBuses() ? '' : 'bad' }}"><div class="label">Buses (RN-004: {{ $nEst }}–{{ $nEst * 2 }})</div><div class="value">{{ $nBus }}</div></div>
    <div class="card kpi {{ $alertas->count() ? 'bad' : '' }}"><div class="label">Alertas pendientes</div><div class="value">{{ $alertas->count() }}</div></div>
</div>

<div class="grid grid-2">
    <section class="card">
        <div class="card-head"><h2>Recorrido de estaciones</h2><small>RN-001 · RN-009</small></div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>#</th><th>Estación</th><th>Accesos / guardias</th><th>Distancia desde anterior</th><th></th></tr></thead>
            <tbody>
            @forelse ($linea->estaciones as $e)
                <tr>
                    <td><span class="badge warn">{{ $e->pivot->orden_estacion }}</span></td>
                    <td><strong>{{ $e->nombre }}</strong><br><small>{{ $e->direccion }} @if($e->tiene_parqueo === 'S')· Parqueo ({{ $e->parqueos->count() }})@endif</small></td>
                    <td>
                        @foreach ($e->accesos as $a)
                            <div><small>{{ $a->nombre }}: @if($a->guardias->isEmpty())<span class="badge bad">sin guardia</span>@else{{ $a->guardias->pluck('nombre_completo')->join(', ') }}@endif</small></div>
                        @endforeach
                        @if ($e->accesos->isEmpty())<small class="muted">Sin accesos</small>@endif
                    </td>
                    <td>
                        @if ($e->pivot->orden_estacion > 1)
                            <form method="POST" action="{{ route('lineas.estaciones.distancia', [$linea, $e]) }}" class="actions">@csrf @method('PATCH')
                                <input class="input input-inline" style="width:96px" type="number" step="0.01" min="0" name="distancia_desde_anterior" value="{{ $e->pivot->distancia_desde_anterior }}" aria-label="Distancia en km">
                                <button class="btn btn-ghost btn-sm">km ✓</button>
                            </form>
                        @else
                            <small class="muted">Inicio</small>
                        @endif
                    </td>
                    <td class="num">
                        <form method="POST" action="{{ route('lineas.estaciones.quitar', [$linea, $e]) }}" onsubmit="return confirm('¿Quitar {{ $e->nombre }} del recorrido?')">@csrf @method('DELETE')
                            <button class="btn btn-ghost btn-sm">Quitar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">La línea aún no tiene estaciones.</td></tr>
            @endforelse
            </tbody>
        </table></div>

        <form method="POST" action="{{ route('lineas.estaciones.agregar', $linea) }}" class="form-row mt-16" style="align-items:end">
            @csrf
            <x-campo name="id_estacion" label="Agregar estación al final" type="select" required>
                <option value="">— Seleccione —</option>
                @foreach ($disponibles as $d)<option value="{{ $d->id_estacion }}">{{ $d->nombre }}</option>@endforeach
            </x-campo>
            @if ($nEst > 0)
                <x-campo name="distancia_desde_anterior" label="Distancia desde {{ $linea->estaciones->last()->nombre }} (km)" type="number" step="0.01" min="0" required />
            @endif
            <div><button class="btn">Agregar</button></div>
        </form>
    </section>

    <div class="stack">
        <section class="card">
            <div class="card-head"><h2>Buses asignados</h2><a class="btn btn-ghost btn-sm" href="{{ route('buses.index') }}">Asignar desde buses</a></div>
            <div class="table-wrap"><table class="table">
                <thead><tr><th>Bus</th><th>Placa</th><th class="num">Capacidad</th><th>Desde</th></tr></thead>
                <tbody>
                @forelse ($linea->buses as $b)
                    <tr><td><a href="{{ route('buses.show', $b) }}">{{ $b->numero_interno }}</a></td><td>{{ $b->placa }}</td><td class="num">{{ $b->capacidad_maxima }}</td><td><small>{{ \Illuminate\Support\Carbon::parse($b->pivot->fecha_inicio)->format('d/m/Y') }}</small></td></tr>
                @empty
                    <tr><td colspan="4" class="empty">Sin buses asignados.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="card">
            <div class="card-head"><h2>Últimos recorridos</h2></div>
            <div class="table-wrap"><table class="table">
                <tbody>
                @forelse ($recorridos as $r)
                    <tr><td><a href="{{ route('recorridos.show', $r) }}">#{{ $r->id_recorrido }}</a></td><td>Bus {{ $r->bus->numero_interno }}</td><td>{{ $r->piloto->nombre_completo }}</td><td><small>{{ $r->fecha_inicio->format('d/m H:i') }}</small></td><td><x-estado :valor="$r->estado" /></td></tr>
                @empty
                    <tr><td class="empty">Sin recorridos.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        </section>

        <section class="card">
            <div class="card-head"><h2>Alertas pendientes</h2></div>
            @forelse ($alertas as $a)
                <p class="mt-16"><x-estado :valor="$a->nivel" /> <strong>{{ str_replace('_', ' ', $a->tipo_alerta) }}</strong> <small>{{ $a->descripcion }}</small></p>
            @empty
                <p class="empty">Sin alertas.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
