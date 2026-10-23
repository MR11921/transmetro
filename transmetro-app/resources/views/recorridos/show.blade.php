@extends('layouts.app')
@section('title', 'Recorrido #'.$r->id_recorrido)
@section('content')
@php
    $cap = $r->bus->capacidad_maxima;
    $visitadas = $r->controles->keyBy('id_estacion');
@endphp
<div class="page-head">
    <div>
        <h1>Recorrido #{{ $r->id_recorrido }} <x-estado :valor="$r->estado" /></h1>
        <p>{{ $r->linea->nombre }} · Bus {{ $r->bus->numero_interno }} (capacidad {{ $cap }}) · Piloto {{ $r->piloto->nombre_completo }} · {{ $r->fecha_inicio->format('d/m/Y H:i') }}</p>
    </div>
    <div class="actions">
        @if ($r->estado === 'PROGRAMADO')
            <form method="POST" action="{{ route('recorridos.iniciar', $r) }}">@csrf @method('PATCH')<button class="btn btn-success">Iniciar recorrido</button></form>
        @endif
        @if ($r->estado === 'EN_CURSO')
            <form method="POST" action="{{ route('recorridos.finalizar', $r) }}" onsubmit="return confirm('¿Finalizar el recorrido?')">@csrf @method('PATCH')<button class="btn">Finalizar</button></form>
        @endif
        @if (in_array($r->estado, ['PROGRAMADO', 'EN_CURSO']) && auth()->user()->puede('recorridos'))
            <form method="POST" action="{{ route('recorridos.cancelar', $r) }}" onsubmit="return confirm('¿Cancelar el recorrido?')">@csrf @method('PATCH')<button class="btn btn-ghost">Cancelar</button></form>
        @endif
    </div>
</div>

<div class="kpis">
    <div class="card kpi"><div class="label">A bordo</div><div class="value">{{ $ocupacion }} / {{ $cap }}</div></div>
    <div class="card kpi {{ $porcentaje < config('transmetro.umbral_baja_ocupacion') ? 'warn' : '' }}"><div class="label">Ocupación</div><div class="value">{{ $porcentaje }} %</div></div>
    <div class="card kpi"><div class="label">Estaciones visitadas</div><div class="value">{{ $r->controles->count() }} / {{ $r->linea->estaciones->count() }}</div></div>
    <div class="card kpi"><div class="label">Abordajes totales</div><div class="value">{{ $r->controles->sum('pasajeros_abordan') }}</div></div>
</div>

<div class="grid grid-2">
    <section class="card">
        <div class="card-head"><h2>Terminal de Control de Pasajeros</h2></div>
        @if ($r->estado === 'EN_CURSO' && $siguiente)
            <p>Parada actual: <strong>{{ $siguiente->nombre }}</strong></p>
            <form method="POST" action="{{ route('recorridos.parada', $r) }}" class="form mt-16">@csrf
                <div class="form-row">
                    <x-campo name="pasajeros_descienden" label="Descienden" type="number" min="0" :max="$ocupacion" value="0" required />
                    <x-campo name="pasajeros_abordan" label="Abordan" type="number" min="0" value="0" required />
                    <x-campo name="en_espera" label="Quedan en andén" type="number" min="0" value="0" />
                </div>
                <small>Si la demanda (a bordo + en andén) alcanza el {{ config('transmetro.umbral_alta_demanda') }} % de la capacidad se genera una alerta. Con ocupación menor al {{ config('transmetro.umbral_baja_ocupacion') }} % se aplican {{ config('transmetro.minutos_espera_adicional') }} min de espera.</small>
                <div class="form-actions"><button class="btn">Registrar parada</button></div>
            </form>
        @elseif ($r->estado === 'EN_CURSO')
            <p class="empty">Se visitaron todas las estaciones. Finalice el recorrido.</p>
        @elseif ($r->estado === 'PROGRAMADO')
            <p class="empty">Inicie el recorrido para registrar pasajeros.</p>
        @else
            <p class="empty">Recorrido cerrado.</p>
        @endif
        <div class="meter mt-16 {{ $porcentaje >= 90 ? 'bad' : ($porcentaje < 25 ? 'warn' : '') }}"><span style="width: {{ min($porcentaje, 100) }}%"></span></div>
    </section>

    <section class="card">
        <div class="card-head"><h2>Estaciones del recorrido</h2></div>
        <ol class="steps">
            @foreach ($r->linea->estaciones as $i => $e)
                @php $c = $visitadas->get($e->id_estacion); $esSiguiente = $siguiente?->id_estacion === $e->id_estacion && $r->estado === 'EN_CURSO'; @endphp
                <li class="{{ $c ? 'done' : ($esSiguiente ? 'next' : '') }}">
                    <span class="dot">{{ $i + 1 }}</span>
                    <span>{{ $e->nombre }}</span>
                    <span class="meta">
                        @if ($c)+{{ $c->pasajeros_abordan }} / −{{ $c->pasajeros_descienden }} → {{ $c->ocupacion_calculada }} · {{ $c->fecha_hora->format('H:i') }}
                        @elseif ($e->pivot->distancia_desde_anterior){{ $e->pivot->distancia_desde_anterior }} km @endif
                    </span>
                </li>
            @endforeach
        </ol>
    </section>
</div>

<section class="card mt-16">
    <div class="card-head"><h2>Alertas del recorrido</h2></div>
    <div class="table-wrap"><table class="table">
        <tbody>
        @forelse ($r->alertas->sortByDesc('id_alerta') as $a)
            <tr><td><x-estado :valor="$a->nivel" /></td><td><strong>{{ str_replace('_', ' ', $a->tipo_alerta) }}</strong><br><small>{{ $a->descripcion }}</small></td><td><x-estado :valor="$a->estado" /></td><td><small>{{ $a->fecha_hora->format('H:i:s') }}</small></td></tr>
        @empty
            <tr><td class="empty">Sin alertas.</td></tr>
        @endforelse
        </tbody>
    </table></div>
</section>
@endsection
