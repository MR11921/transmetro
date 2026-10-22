@extends('layouts.app')
@section('title', 'Auditoría')
@section('content')
<div class="page-head"><div><h1>Auditoría</h1><p>RF-024 · RNF-009 · RNF-012 · Operaciones registradas</p></div></div>
<div class="card">
    <form class="filters" method="GET">
        <div class="field"><label for="id_usuario">Usuario</label>
            <select id="id_usuario" name="id_usuario" class="input"><option value="">Todos</option>@foreach($usuarios as $usr)<option value="{{ $usr->id_usuario }}" @selected(request('id_usuario') == $usr->id_usuario)>{{ $usr->usuario }}</option>@endforeach</select>
        </div>
        <div class="field"><label for="modulo">Módulo</label>
            <select id="modulo" name="modulo" class="input"><option value="">Todos</option>@foreach($modulos as $m)<option @selected(request('modulo') === $m)>{{ $m }}</option>@endforeach</select>
        </div>
        <div class="field"><label for="desde">Desde</label><input id="desde" type="date" class="input" name="desde" value="{{ request('desde') }}"></div>
        <div class="field"><label for="hasta">Hasta</label><input id="hasta" type="date" class="input" name="hasta" value="{{ request('hasta') }}"></div>
        <button class="btn btn-ghost">Filtrar</button>
    </form>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Fecha y hora</th><th>Usuario</th><th>Módulo</th><th>Acción</th><th>Registro afectado</th><th>Detalle</th></tr></thead>
        <tbody>
        @forelse ($registros as $a)
            <tr>
                <td><small>{{ $a->fecha_hora->format('d/m/Y H:i:s') }}</small></td>
                <td>{{ $a->usuario->usuario }}</td>
                <td>{{ $a->modulo }}</td>
                <td><span class="badge">{{ $a->accion }}</span></td>
                <td><small>{{ $a->tabla_afectada }}{{ $a->id_registro_afectado ? ' #'.$a->id_registro_afectado : '' }}</small></td>
                <td><small>{{ \Illuminate\Support\Str::limit($a->detalle, 120) }}</small></td>
            </tr>
        @empty
            <tr><td colspan="6" class="empty">Sin registros.</td></tr>
        @endforelse
        </tbody>
    </table></div>
    {{ $registros->links('partials.paginacion') }}
</div>
@endsection
