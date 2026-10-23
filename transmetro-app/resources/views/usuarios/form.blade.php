@extends('layouts.app')
@section('title', 'Usuarios')
@section('content')
@php $editando = $usuario->exists; @endphp
<div class="page-head"><div><h1>{{ $editando ? 'Editar usuario' : 'Nuevo usuario' }}</h1><p>Datos de acceso y rol del usuario</p></div></div>
<div class="card" style="max-width: 800px">
    <form method="POST" class="form" action="{{ $editando ? route('usuarios.update', $usuario) : route('usuarios.store') }}">
        @csrf @if($editando) @method('PUT') @endif
        <div class="form-row">
            <x-campo name="nombre" label="Nombre" :value="$usuario->nombre" required />
            <x-campo name="apellido" label="Apellido" :value="$usuario->apellido" required />
        </div>
        <div class="form-row">
            <x-campo name="usuario" label="Usuario" :value="$usuario->usuario" required />
            <x-campo name="correo" label="Correo" type="email" :value="$usuario->correo" required />
        </div>
        <div class="form-row">
            <x-campo name="id_rol" label="Rol" type="select" required>
                @foreach ($roles as $rol)<option value="{{ $rol->id_rol }}" @selected(old('id_rol', $usuario->id_rol) == $rol->id_rol)>{{ str_replace('_', ' ', $rol->nombre) }} — {{ $rol->descripcion }}</option>@endforeach
            </x-campo>
            <x-campo name="estado" label="Estado" type="select" required>
                @foreach (['ACTIVO', 'INACTIVO'] as $e)<option @selected(old('estado', $usuario->estado) === $e)>{{ $e }}</option>@endforeach
            </x-campo>
        </div>
        <div class="form-row">
            <x-campo name="password" label="{{ $editando ? 'Nueva contraseña (opcional)' : 'Contraseña' }}" type="password" :required="!$editando" autocomplete="new-password" />
            <x-campo name="password_confirmation" label="Confirmar contraseña" type="password" :required="!$editando" autocomplete="new-password" />
        </div>
        <small>Mínimo 8 caracteres con letras y números. Se almacena cifrada.</small>
        <div class="form-actions"><a class="btn btn-ghost" href="{{ route('usuarios.index') }}">Cancelar</a><button class="btn">Guardar</button></div>
    </form>
</div>
@endsection
