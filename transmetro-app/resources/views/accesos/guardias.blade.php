@extends('layouts.app')
@section('title', 'Seguridad en accesos')
@section('content')
@php $sinGuardia = $accesos->filter(fn ($a) => $a->estado === 'ACTIVO' && $a->guardias->isEmpty())->count(); @endphp
<div class="page-head">
    <div><h1>Seguridad en accesos</h1><p>RF-006 · RN-007: cada acceso debe tener al menos un guardia</p></div>
    <a class="btn btn-ghost" href="{{ route('catalogos.index', 'accesos') }}">Administrar accesos</a>
</div>
@if ($sinGuardia)<div class="alert alert-error">{{ $sinGuardia }} acceso(s) activo(s) sin guardia asignado.</div>@endif
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>Estación</th><th>Acceso</th><th>Estado</th><th>Guardias vigentes</th><th>Asignar guardia</th></tr></thead>
    <tbody>
    @forelse ($accesos as $a)
        <tr>
            <td>{{ $a->estacion->nombre }}</td>
            <td><strong>{{ $a->nombre }}</strong><br><small>{{ $a->tipo }}</small></td>
            <td><x-estado :valor="$a->estado" /></td>
            <td>
                @forelse ($a->guardias as $g)
                    <div class="actions">
                        <span>{{ $g->nombre_completo }}</span>
                        <form method="POST" action="{{ route('seguridad.retirar', [$a, $g]) }}" class="inline-form">@csrf @method('DELETE')<button class="btn-link">retirar</button></form>
                    </div>
                @empty
                    <span class="badge bad">Sin guardia</span>
                @endforelse
            </td>
            <td>
                <form method="POST" action="{{ route('seguridad.asignar', $a) }}" class="actions">@csrf
                    <select name="id_guardia" class="input input-inline" required aria-label="Guardia">
                        <option value="">— Guardia —</option>
                        @foreach ($guardias as $g)<option value="{{ $g->id_guardia }}">{{ $g->nombre_completo }}</option>@endforeach
                    </select>
                    <button class="btn btn-sm">Asignar</button>
                </form>
            </td>
        </tr>
    @empty
        <tr><td colspan="5" class="empty">No hay accesos registrados.</td></tr>
    @endforelse
    </tbody>
</table></div></div>
@endsection
