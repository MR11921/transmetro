@extends('layouts.app')
@section('title', 'Recorridos')
@section('content')
<div class="page-head">
    <div><h1>Recorridos</h1><p>RF-019 · Seguimiento de recorridos y Terminal de Control de Pasajeros</p></div>
    @if(auth()->user()->puede('recorridos'))<a class="btn" href="{{ route('recorridos.create') }}">Programar recorrido</a>@endif
</div>
<div class="card">
    <form class="filters" method="GET">
        <div class="field"><label for="estado">Estado</label>
            <select id="estado" name="estado" class="input"><option value="">Todos</option>@foreach(['PROGRAMADO','EN_CURSO','FINALIZADO','CANCELADO'] as $e)<option value="{{ $e }}" @selected(request('estado') === $e)>{{ str_replace('_',' ',$e) }}</option>@endforeach</select>
        </div>
        <button class="btn btn-ghost">Filtrar</button>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>#</th><th>Línea</th><th>Bus</th><th>Piloto</th><th>Inicio</th><th>Fin</th><th>Estado</th><th></th></tr></thead>
        <tbody>
        @forelse ($recorridos as $r)
            <tr>
                <td>{{ $r->id_recorrido }}</td><td>{{ $r->linea->nombre }}</td><td>{{ $r->bus->numero_interno }}</td><td>{{ $r->piloto->nombre_completo }}</td>
                <td>{{ $r->fecha_inicio->format('d/m/Y H:i') }}</td><td>{{ $r->fecha_fin?->format('d/m/Y H:i') ?? '—' }}</td>
                <td><x-estado :valor="$r->estado" /></td>
                <td class="num"><a class="btn btn-ghost btn-sm" href="{{ route('recorridos.show', $r) }}">{{ $r->estado === 'EN_CURSO' ? 'Abrir TCP' : 'Ver' }}</a></td>
            </tr>
        @empty
            <tr><td colspan="8" class="empty">No hay recorridos.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $recorridos->links('partials.paginacion') }}
</div>
@endsection
