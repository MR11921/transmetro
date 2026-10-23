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
        {{-- Solo para el rol Piloto: con qué piloto entra al sistema y opera sus recorridos en el TCP --}}
        <fieldset class="solo-rol" id="campo-piloto" data-rol="{{ $rolPiloto }}">
            <legend>Piloto</legend>
            <x-campo name="id_piloto" label="Este usuario conduce como" type="select">
                <option value="nuevo" @selected(old('id_piloto', $pilotoActual ?? 'nuevo') == 'nuevo')>Crear un piloto nuevo con el nombre, apellido y correo de arriba</option>
                @foreach ($pilotos as $p)
                    <option value="{{ $p->id_piloto }}" @selected(old('id_piloto', $pilotoActual) == $p->id_piloto)>{{ $p->nombre_completo }}{{ $p->id_piloto === $pilotoActual ? ' (vinculado ahora)' : '' }}</option>
                @endforeach
            </x-campo>
            <small>El piloto solo verá y operará en el TCP los recorridos asignados a este piloto. Si ya existe en el catálogo de pilotos, elíjalo para no duplicarlo.</small>
        </fieldset>
        <div class="form-row">
            <x-campo name="password" label="{{ $editando ? 'Nueva contraseña (opcional)' : 'Contraseña' }}" type="password" :required="!$editando" autocomplete="new-password" />
            <x-campo name="password_confirmation" label="Confirmar contraseña" type="password" :required="!$editando" autocomplete="new-password" />
        </div>
        <small>Mínimo 8 caracteres con letras y números. Se almacena cifrada.</small>
        <div class="form-actions"><a class="btn btn-ghost" href="{{ route('usuarios.index') }}">Cancelar</a><button class="btn">Guardar</button></div>
    </form>
</div>
<script>
    // Muestra el bloque "Piloto" solo cuando el rol elegido es Piloto
    (function () {
        const rol = document.getElementById('id_rol');
        const bloque = document.getElementById('campo-piloto');
        const actualizar = () => { bloque.hidden = rol.value !== bloque.dataset.rol; };
        rol.addEventListener('change', actualizar);
        actualizar();
    })();
</script>
@endsection
