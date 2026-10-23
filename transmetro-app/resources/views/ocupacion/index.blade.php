@extends('layouts.app')
@section('title', 'Ocupación de buses')
@section('content')
<div class="page-head">
    <div><h1>Ocupación de buses</h1><p>Abra el TCP de un bus para registrar abordajes y descensos</p></div>
</div>

@if ($sinVinculo)
    <div class="alert alert-warn" role="status">Su usuario aún no está vinculado a un piloto, por eso no ve recorridos. Pida al administrador que lo asigne en <strong>Pilotos → Usuario del sistema</strong>.</div>
@endif

<div class="leyenda">
    <span><i class="sw ok"></i> Normal</span>
    <span><i class="sw warn"></i> Baja ocupación (&lt; {{ $umbralBaja }} %): +{{ $minutos }} min por estación</span>
    <span><i class="sw bad"></i> Bus lleno: si la demanda llega a {{ $umbralAlta }} % se pide otra unidad</span>
</div>

<section class="mt-16">
    <div class="card-head"><h2>Buses en ruta ({{ $enCurso->count() }})</h2></div>
    <div class="grid grid-3">
    @forelse ($enCurso as $r)
        @php
            $pct = $r->porcentajeOcupacion();
            $nivel = $pct >= 90 ? 'bad' : ($pct < $umbralBaja ? 'warn' : 'ok');
            $texto = $pct >= 90 ? 'Casi lleno' : ($pct < $umbralBaja ? 'Baja ocupación' : 'Normal');
        @endphp
        <article class="card bus-card {{ $nivel }}">
            <div class="card-head">
                <h3><x-icono nombre="bus" :tam="18" /> Bus {{ $r->bus->numero_interno }}</h3>
                <span class="badge {{ $nivel }}">{{ $texto }}</span>
            </div>
            <div class="big-num">{{ $r->ocupacionActual() }}<small> / {{ $r->bus->capacidad_maxima }} pasajeros</small></div>
            <div class="meter {{ $nivel === 'ok' ? '' : $nivel }}"><span style="width: {{ min($pct, 100) }}%"></span></div>
            <dl class="dl mt-16">
                <dt>Línea</dt><dd>{{ $r->linea->nombre }}</dd>
                <dt>Piloto</dt><dd>{{ $r->piloto->nombre_completo }}</dd>
                <dt>Próxima</dt><dd>{{ $r->siguienteEstacion()?->nombre ?? 'Fin de línea' }}</dd>
            </dl>
            @if (auth()->user()->puede('tcp'))
                <a class="btn btn-block mt-16" href="{{ route('recorridos.show', $r) }}">Abrir TCP <x-icono nombre="flecha" :tam="14" /></a>
            @endif
        </article>
    @empty
        <div class="card empty-state">
            <x-icono nombre="bus" :tam="32" />
            <p><strong>No hay buses en ruta.</strong></p>
            <p class="muted">Cuando un recorrido se inicie, aquí verá su ocupación en tiempo real.</p>
        </div>
    @endforelse
    </div>
</section>

<div class="grid grid-2 mt-24">
    <section class="card">
        <div class="card-head"><h2>Recorridos programados</h2>@if(auth()->user()->puede('recorridos'))<a class="btn btn-ghost btn-sm" href="{{ route('recorridos.create') }}"><x-icono nombre="mas" :tam="14" /> Programar</a>@endif</div>
        <div class="table-wrap"><table class="table">
            <tbody>
            @forelse ($programados as $r)
                <tr>
                    <td><strong>Bus {{ $r->bus->numero_interno }}</strong><br><small>{{ $r->linea->nombre }} · {{ $r->piloto->nombre_completo }}</small></td>
                    <td class="num"><small>{{ $r->fecha_inicio->format('d/m H:i') }}</small></td>
                    <td class="num">@if(auth()->user()->puede('tcp'))<a class="btn btn-ghost btn-sm" href="{{ route('recorridos.show', $r) }}">Ver</a>@endif</td>
                </tr>
            @empty
                <tr><td class="empty">No hay recorridos programados.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>

    <section class="card">
        <div class="card-head"><h2>Demanda y esperas recientes</h2>@if(auth()->user()->puede('alertas'))<a class="btn btn-ghost btn-sm" href="{{ route('alertas.index') }}">Ver alertas</a>@endif</div>
        <div class="table-wrap"><table class="table">
            <tbody>
            @forelse ($alertas as $a)
                <tr>
                    <td><x-estado :valor="$a->estado" /></td>
                    <td><strong>{{ $a->tipo_alerta === 'ALTA_DEMANDA' ? 'Alta demanda' : 'Espera adicional' }}</strong><br><small>{{ $a->descripcion }}</small></td>
                    <td class="num"><small>{{ $a->fecha_hora->diffForHumans() }}</small></td>
                </tr>
            @empty
                <tr><td class="empty">Sin eventos de demanda o espera.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</div>
@endsection
