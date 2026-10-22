@extends('layouts.app')
@section('title', 'Líneas')
@section('content')
@php $editando = $linea->exists; @endphp
<div class="page-head"><div><h1>{{ $editando ? 'Editar línea' : 'Nueva línea' }}</h1><p>RF-004 · RN-008: toda línea pertenece a una municipalidad</p></div></div>
<div class="card" style="max-width: 800px">
    <form method="POST" class="form" action="{{ $editando ? route('lineas.update', $linea) : route('lineas.store') }}">
        @csrf @if($editando) @method('PUT') @endif
        <div class="form-row">
            <x-campo name="nombre" label="Nombre" :value="$linea->nombre" required />
            <x-campo name="id_municipalidad" label="Municipalidad" type="select" required>
                <option value="">— Seleccione —</option>
                @foreach ($municipalidades as $m)<option value="{{ $m->id_municipalidad }}" @selected(old('id_municipalidad', $linea->id_municipalidad) == $m->id_municipalidad)>{{ $m->nombre }}</option>@endforeach
            </x-campo>
            <x-campo name="estado" label="Estado" type="select" required>
                @foreach (['ACTIVA', 'INACTIVA'] as $e)<option @selected(old('estado', $linea->estado) === $e)>{{ $e }}</option>@endforeach
            </x-campo>
        </div>
        <x-campo name="descripcion" label="Descripción" type="textarea" :value="$linea->descripcion" />
        <div class="form-actions"><a class="btn btn-ghost" href="{{ $editando ? route('lineas.show', $linea) : route('lineas.index') }}">Cancelar</a><button class="btn">Guardar</button></div>
    </form>
</div>
@endsection
