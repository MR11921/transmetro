@extends('layouts.app')
@section('title', 'Incidentes')
@section('content')
@php $gestiona = auth()->user()->tieneRol('ADMINISTRADOR', 'ADMINISTRADOR_TRANSPORTE', 'OPERADOR_ESTACION'); @endphp
<div class="page-head">
    <div><h1>Incidentes</h1><p>RF-022 · Buses, estaciones, pasajeros, dispositivos, comunicaciones y seguridad</p></div>
    <a class="btn" href="{{ route('incidentes.create') }}">Registrar incidente</a>
</div>
<div class="card">
    <form class="filters" method="GET">
        <div class="field"><label for="estado">Estado</label>
            <select id="estado" name="estado" class="input"><option value="">Todos</option>@foreach(['ABIERTO','EN_PROCESO','CERRADO'] as $e)<option value="{{ $e }}" @selected(request('estado') === $e)>{{ str_replace('_',' ',$e) }}</option>@endforeach</select>
        </div>
        <button class="btn btn-ghost">Filtrar</button>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th>Relacionado</th><th>Registró</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($incidentes as $i)
            <tr>
                <td><small>{{ $i->fecha_hora->format('d/m/Y H:i') }}</small></td>
                <td>{{ $i->tipo_incidente }}</td>
                <td><small>{{ $i->descripcion }}</small></td>
                <td><small>{{ collect([$i->bus ? 'Bus '.$i->bus->numero_interno : null, $i->estacion?->nombre, $i->dispositivo?->codigo_inventario])->filter()->join(' · ') ?: '—' }}</small></td>
                <td><small>{{ $i->usuario->usuario }}</small></td>
                <td><x-estado :valor="$i->estado" /></td>
                <td class="num">
                    @if ($gestiona && $i->estado !== 'CERRADO')
                        <form method="POST" action="{{ route('incidentes.estado', $i) }}" class="actions" style="justify-content:flex-end">@csrf @method('PATCH')
                            @if ($i->estado === 'ABIERTO')<button class="btn btn-sm btn-ghost" name="estado" value="EN_PROCESO">En proceso</button>@endif
                            <button class="btn btn-sm btn-success" name="estado" value="CERRADO">Cerrar</button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">No hay incidentes.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $incidentes->links('partials.paginacion') }}
</div>
@endsection
