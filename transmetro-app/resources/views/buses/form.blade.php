@extends('layouts.app')
@section('title', 'Buses')
@section('content')
@php $editando = $bus->exists; @endphp
<div class="page-head"><div><h1>{{ $editando ? 'Editar bus '.$bus->numero_interno : 'Nuevo bus' }}</h1><p>RF-007</p></div></div>
<div class="card" style="max-width: 800px">
    <form method="POST" class="form" action="{{ $editando ? route('buses.update', $bus) : route('buses.store') }}">
        @csrf @if($editando) @method('PUT') @endif
        <div class="form-row">
            <x-campo name="numero_interno" label="Número interno" :value="$bus->numero_interno" required />
            <x-campo name="placa" label="Placa" :value="$bus->placa" required />
            <x-campo name="capacidad_maxima" label="Capacidad máxima" type="number" min="1" :value="$bus->capacidad_maxima" required />
            <x-campo name="estado" label="Estado" type="select" required>
                @foreach (['ACTIVO', 'INACTIVO', 'MANTENIMIENTO'] as $e)<option @selected(old('estado', $bus->estado) === $e)>{{ $e }}</option>@endforeach
            </x-campo>
        </div>
        @unless ($editando)
            <x-campo name="id_parqueo" label="Parqueo inicial (RN-006)" type="select" required>
                <option value="">— Seleccione —</option>
                @foreach ($parqueos as $p)<option value="{{ $p->id_parqueo }}" @selected(old('id_parqueo') == $p->id_parqueo)>{{ $p->codigo }} — {{ $p->estacion->nombre }}</option>@endforeach
            </x-campo>
            @if ($parqueos->isEmpty())<div class="alert alert-warn">No hay parqueos disponibles. Registre uno en Parqueos antes de crear el bus.</div>@endif
        @endunless
        <div class="form-actions"><a class="btn btn-ghost" href="{{ $editando ? route('buses.show', $bus) : route('buses.index') }}">Cancelar</a><button class="btn">Guardar</button></div>
    </form>
</div>
@endsection
