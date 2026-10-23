@extends('layouts.app')
@section('title', 'Roles y permisos')
@section('content')
<div class="page-head">
    <div><h1>Roles y permisos</h1><p>Cada rol solo ve y usa las opciones marcadas</p></div>
    <a class="btn" href="{{ route('usuarios.index') }}">Administrar usuarios</a>
</div>

<div class="kpis">
    @foreach ($roles as $rol)
        <div class="card kpi"><div class="label">{{ str_replace('_', ' ', $rol->nombre) }}</div><div class="value">{{ $rol->usuarios_count }}</div><small>{{ $rol->descripcion }}</small></div>
    @endforeach
</div>

<section class="card">
    <div class="card-head"><h2>Matriz de acceso por módulo</h2><small><span class="check">✓</span> permitido · — sin acceso</small></div>
    <div class="table-wrap"><table class="table table-matriz">
        <thead><tr><th>Opción</th>@foreach ($roles as $rol)<th class="center">{{ str_replace('_', ' ', $rol->nombre) }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach ($modulos as $m)
            <tr class="row-group"><td colspan="{{ 1 + $roles->count() }}"><x-icono :nombre="$m['icono']" :tam="16" /> Módulo {{ $m['numero'] }} · {{ $m['nombre'] }}</td></tr>
            @foreach ($m['opciones'] as [$label, , , $permiso])
                <tr>
                    <td>{{ $label }}</td>
                    @foreach ($roles as $rol)
                        <td class="center">@if ($permite($permiso, $rol->nombre))<span class="check" aria-label="Permitido">✓</span>@else<span class="muted" aria-label="Sin acceso">—</span>@endif</td>
                    @endforeach
                </tr>
            @endforeach
        @endforeach
        </tbody>
    </table></div>
</section>
@endsection
