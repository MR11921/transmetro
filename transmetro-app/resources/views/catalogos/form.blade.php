@extends('layouts.app')
@section('title', $def['titulo'])
@section('content')
@php $editando = $registro->exists; @endphp
<div class="page-head">
    <div><h1>{{ $editando ? 'Editar' : 'Nuevo' }} {{ $def['singular'] }}</h1><p>{{ $def['titulo'] }}</p></div>
</div>
<div class="card" style="max-width: 800px">
    <form method="POST" class="form" action="{{ $editando ? route('catalogos.update', [$def['clave'], $registro->getKey()]) : route('catalogos.store', $def['clave']) }}">
        @csrf
        @if ($editando) @method('PUT') @endif
        <div class="form-row">
        @foreach ($def['campos'] as $campo => $c)
            @php
                $requerido = str_contains($c[2], 'required');
                $valor = $registro->$campo instanceof \Carbon\Carbon ? $registro->$campo->format('Y-m-d') : $registro->$campo;
            @endphp
            @if ($c[1] === 'enum')
                <x-campo :name="$campo" :label="$c[0]" type="select" :required="$requerido">
                    @unless($requerido)<option value="">—</option>@endunless
                    @foreach ($c['opciones'] as $op)
                        <option value="{{ $op }}" @selected(old($campo, $valor ?? ($requerido ? $c['opciones'][0] : null)) == $op)>{{ str_replace('_', ' ', $op) }}</option>
                    @endforeach
                </x-campo>
            @elseif ($c[1] === 'select')
                <x-campo :name="$campo" :label="$c[0]" type="select" :required="$requerido">
                    <option value="">— Seleccione —</option>
                    @foreach ($opciones[$campo] as $id => $texto)
                        <option value="{{ $id }}" @selected(old($campo, $valor) == $id)>{{ $texto }}</option>
                    @endforeach
                </x-campo>
            @else
                <x-campo :name="$campo" :label="$c[0]" :type="$c[1]" :value="$valor" :required="$requerido" :step="$c[1] === 'number' ? 'any' : null" />
            @endif
        @endforeach
        </div>
        <div class="form-actions">
            <a class="btn btn-ghost" href="{{ route('catalogos.index', $def['clave']) }}">Cancelar</a>
            <button class="btn">Guardar</button>
        </div>
    </form>
</div>
@endsection
