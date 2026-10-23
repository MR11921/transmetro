@extends('layouts.app')
@section('title', 'Usuarios')
@section('content')
<div class="page-head">
    <div><h1>Usuarios</h1><p>Gestión de usuarios, roles y permisos</p></div>
    <a class="btn" href="{{ route('usuarios.create') }}">Nuevo usuario</a>
</div>
<div class="card"><div class="table-wrap"><table class="table">
    <thead><tr><th>Usuario</th><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Creado</th><th></th></tr></thead>
    <tbody>
    @foreach ($usuarios as $usr)
        <tr>
            <td><strong>{{ $usr->usuario }}</strong></td>
            <td>{{ $usr->nombre_completo }}</td>
            <td>{{ $usr->correo }}</td>
            <td><span class="badge info">{{ str_replace('_', ' ', $usr->rol->nombre) }}</span></td>
            <td><x-estado :valor="$usr->estado" /></td>
            <td><small>{{ $usr->fecha_creacion?->format('d/m/Y') }}</small></td>
            <td class="num">
                <div class="actions" style="justify-content:flex-end">
                    <a class="btn btn-ghost btn-sm" href="{{ route('usuarios.edit', $usr) }}">Editar</a>
                    @if ($usr->id_usuario !== auth()->id())
                        <form method="POST" action="{{ route('usuarios.estado', $usr) }}" class="inline-form">@csrf @method('PATCH')
                            <button class="btn btn-sm {{ $usr->estado === 'ACTIVO' ? 'btn-danger' : 'btn-success' }}">{{ $usr->estado === 'ACTIVO' ? 'Desactivar' : 'Activar' }}</button>
                        </form>
                    @endif
                </div>
            </td>
        </tr>
    @endforeach
    </tbody>
</table></div></div>

<section class="card mt-16">
    <div class="card-head"><h2>Permisos por rol</h2></div>
    <div class="table-wrap"><table class="table">
        <thead><tr><th>Módulo</th>@foreach (config('transmetro.roles') as $rol)<th>{{ str_replace('_', ' ', $rol) }}</th>@endforeach</tr></thead>
        <tbody>
        @foreach (config('transmetro.permisos') as $modulo => $roles)
            <tr><td>{{ str_replace('_', ' ', ucfirst($modulo)) }}</td>
                @foreach (config('transmetro.roles') as $rol)<td>@if(in_array('*', $roles) || in_array($rol, $roles))<span class="badge ok">Sí</span>@else<span class="muted">—</span>@endif</td>@endforeach
            </tr>
        @endforeach
        </tbody>
    </table></div>
</section>
@endsection
