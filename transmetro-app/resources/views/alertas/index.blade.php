@extends('layouts.app')
@section('title', 'Alertas')
@section('content')
<div class="page-head"><div><h1>Alertas operativas</h1><p>RF-017 · Alta demanda, baja ocupación, fallas de dispositivos e incidentes</p></div></div>
<div class="card">
    <form class="filters" method="GET">
        <div class="field"><label for="estado">Estado</label>
            <select id="estado" name="estado" class="input">@foreach(['PENDIENTE','ATENDIDA','CANCELADA','TODAS'] as $e)<option @selected($estado === $e)>{{ $e }}</option>@endforeach</select>
        </div>
        <div class="field"><label for="tipo">Tipo</label>
            <select id="tipo" name="tipo" class="input"><option value="">Todos</option>@foreach($tipos as $t)<option value="{{ $t }}" @selected(request('tipo') === $t)>{{ str_replace('_',' ',$t) }}</option>@endforeach</select>
        </div>
        <button class="btn btn-ghost">Filtrar</button>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Fecha</th><th>Nivel</th><th>Tipo</th><th>Descripción</th><th>Bus / estación</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($alertas as $a)
            <tr>
                <td><small>{{ $a->fecha_hora->format('d/m/Y H:i') }}</small></td>
                <td><x-estado :valor="$a->nivel" /></td>
                <td>{{ str_replace('_', ' ', $a->tipo_alerta) }}</td>
                <td><small>{{ $a->descripcion }}</small></td>
                <td><small>{{ $a->bus ? 'Bus '.$a->bus->numero_interno : '' }} {{ $a->estacion?->nombre }}</small>
                    @if ($a->recorrido && auth()->user()->puede('tcp'))<br><a href="{{ route('recorridos.show', $a->recorrido) }}"><small>Recorrido #{{ $a->id_recorrido }}</small></a>@endif</td>
                <td><x-estado :valor="$a->estado" /></td>
                <td class="num">
                    @if ($a->estado === 'PENDIENTE')
                        <div class="actions" style="justify-content:flex-end">
                            <form method="POST" action="{{ route('alertas.estado', $a) }}">@csrf @method('PATCH')<input type="hidden" name="estado" value="ATENDIDA"><button class="btn btn-sm btn-success">Atender</button></form>
                            <form method="POST" action="{{ route('alertas.estado', $a) }}">@csrf @method('PATCH')<input type="hidden" name="estado" value="CANCELADA"><button class="btn btn-sm btn-ghost">Cancelar</button></form>
                        </div>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="empty">No hay alertas con ese filtro.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $alertas->links('partials.paginacion') }}
</div>
@endsection
