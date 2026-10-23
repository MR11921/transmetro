@extends('layouts.app')
@section('title', 'Operadores')
@section('content')
<div class="page-head"><div><h1>Operadores de estación</h1><p>Usuarios con rol de operador asignados a estaciones</p></div></div>
<div class="card">
    <form method="POST" action="{{ route('operadores.store') }}" class="form-row" style="align-items:end">@csrf
        <x-campo name="id_usuario" label="Operador" type="select" required>
            <option value="">— Seleccione —</option>
            @foreach ($usuarios as $usr)<option value="{{ $usr->id_usuario }}">{{ $usr->nombre_completo }} ({{ $usr->usuario }})</option>@endforeach
        </x-campo>
        <x-campo name="id_estacion" label="Estación" type="select" required>
            <option value="">— Seleccione —</option>
            @foreach ($estaciones as $e)<option value="{{ $e->id_estacion }}">{{ $e->nombre }}</option>@endforeach
        </x-campo>
        <div><button class="btn">Asignar</button></div>
    </form>
</div>
<div class="card mt-16"><div class="table-wrap"><table class="table">
    <thead><tr><th>Operador</th><th>Estación</th><th>Desde</th><th>Estado</th><th></th></tr></thead>
    <tbody>
    @forelse ($asignaciones as $a)
        <tr>
            <td>{{ $a->usuario->nombre_completo }}</td><td>{{ $a->estacion->nombre }}</td><td>{{ $a->fecha_asignacion->format('d/m/Y') }}</td><td><x-estado :valor="$a->estado" /></td>
            <td class="num">@if($a->estado === 'ACTIVO')<form method="POST" action="{{ route('operadores.desactivar', $a) }}">@csrf @method('PATCH')<button class="btn btn-ghost btn-sm">Desactivar</button></form>@endif</td>
        </tr>
    @empty
        <tr><td colspan="5" class="empty">Sin asignaciones.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
@endsection
