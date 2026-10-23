@extends('layouts.app')
@section('title', 'Bus '.$bus->numero_interno)
@section('content')
<div class="page-head">
    <div><h1>Bus {{ $bus->numero_interno }} <x-estado :valor="$bus->estado" /></h1><p>Placa {{ $bus->placa }} · Capacidad {{ $bus->capacidad_maxima }} pasajeros</p></div>
    <a class="btn btn-ghost" href="{{ route('buses.edit', $bus) }}">Editar</a>
</div>

<div class="grid grid-2">
    <section class="card">
        <div class="card-head"><h2>Línea</h2></div>
        <p>Línea actual: <strong>{{ $lineaActual?->nombre ?? 'Sin línea asignada' }}</strong></p>
        <form method="POST" action="{{ route('buses.linea', $bus) }}" class="form-row mt-16" style="align-items:end">@csrf
            <x-campo name="id_linea" label="Asignar a línea" type="select" required>
                <option value="">— Seleccione —</option>
                @foreach ($lineas as $l)
                    <option value="{{ $l->id_linea }}" @disabled($lineaActual?->id_linea === $l->id_linea)>{{ $l->nombre }} ({{ $l->buses_count }}/{{ $l->estaciones_count * 2 }} buses)</option>
                @endforeach
            </x-campo>
            <div><button class="btn">Asignar</button></div>
        </form>
        @if ($lineaActual)
            <form method="POST" action="{{ route('buses.linea.liberar', $bus) }}" class="mt-16">@csrf @method('DELETE')<button class="btn-link">Retirar de {{ $lineaActual->nombre }}</button></form>
        @endif
        <h3 class="mt-24">Historial de líneas</h3>
        <div class="table-wrap"><table class="table">
            <tbody>
            @forelse ($bus->asignacionesLinea as $a)
                <tr><td>{{ $a->linea->nombre }}</td><td><small>{{ $a->fecha_inicio->format('d/m/Y H:i') }} → {{ $a->fecha_fin?->format('d/m/Y H:i') ?? 'vigente' }}</small></td></tr>
            @empty
                <tr><td class="empty">Sin asignaciones.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>

    <section class="card">
        <div class="card-head"><h2>Parqueo</h2></div>
        <p>Parqueo actual: @if($parqueoActual)<strong>{{ $parqueoActual->codigo }}</strong> — {{ $parqueoActual->estacion->nombre }}@else<span class="badge bad">Sin parqueo</span>@endif</p>
        <form method="POST" action="{{ route('buses.parqueo', $bus) }}" class="form-row mt-16" style="align-items:end">@csrf
            <x-campo name="id_parqueo" label="Cambiar a parqueo" type="select" required>
                <option value="">— Seleccione —</option>
                @foreach ($parqueos as $p)<option value="{{ $p->id_parqueo }}">{{ $p->codigo }} — {{ $p->estacion->nombre }}</option>@endforeach
            </x-campo>
            <div><button class="btn">Cambiar</button></div>
        </form>
        <h3 class="mt-24">Historial de parqueos</h3>
        <div class="table-wrap"><table class="table">
            <tbody>
            @forelse ($bus->historialParqueo as $h)
                <tr><td>{{ $h->parqueo->codigo }}</td><td>{{ $h->parqueo->estacion->nombre }}</td><td><small>{{ $h->fecha_inicio->format('d/m/Y H:i') }} → {{ $h->fecha_fin?->format('d/m/Y H:i') ?? 'vigente' }}</small></td></tr>
            @empty
                <tr><td class="empty">Sin historial.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
</div>

<div class="grid grid-2 mt-16">
    <section class="card">
        <div class="card-head"><h2>Recorridos</h2></div>
        <div class="table-wrap"><table class="table"><tbody>
            @forelse ($recorridos as $r)
                <tr><td><a href="{{ route('recorridos.show', $r) }}">#{{ $r->id_recorrido }}</a></td><td>{{ $r->linea->nombre }}</td><td>{{ $r->piloto->nombre_completo }}</td><td><small>{{ $r->fecha_inicio->format('d/m H:i') }}</small></td><td><x-estado :valor="$r->estado" /></td></tr>
            @empty
                <tr><td class="empty">Sin recorridos.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
    <section class="card">
        <div class="card-head"><h2>Dispositivos a bordo</h2></div>
        <div class="table-wrap"><table class="table"><tbody>
            @forelse ($bus->dispositivos as $d)
                <tr><td>{{ $d->tipo }}</td><td>{{ $d->codigo_inventario }}</td><td><x-estado :valor="$d->estado" /></td></tr>
            @empty
                <tr><td class="empty">Sin dispositivos (TCP) registrados.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>
</div>
@endsection
