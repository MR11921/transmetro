@extends('layouts.app')
@section('title', 'Programar recorrido')
@section('content')
<div class="page-head"><div><h1>Programar recorrido</h1><p>El recorrido se realiza en la línea asignada al bus</p></div></div>
<div class="card" style="max-width: 800px">
    @if ($buses->isEmpty())<div class="alert alert-warn">No hay buses activos con línea asignada.</div>@endif
    <form method="POST" action="{{ route('recorridos.store') }}" class="form">@csrf
        <div class="form-row">
            <x-campo name="id_bus" label="Bus" type="select" required>
                <option value="">— Seleccione —</option>
                @foreach ($buses as $b)<option value="{{ $b->id_bus }}" @selected(old('id_bus') == $b->id_bus)>{{ $b->numero_interno }} — {{ $b->linea_nombre }} (cap. {{ $b->capacidad_maxima }})</option>@endforeach
            </x-campo>
            <x-campo name="id_piloto" label="Piloto" type="select" required>
                <option value="">— Seleccione —</option>
                @foreach ($pilotos as $p)<option value="{{ $p->id_piloto }}" @selected(old('id_piloto') == $p->id_piloto)>{{ $p->nombre_completo }}</option>@endforeach
            </x-campo>
            <x-campo name="fecha_inicio" label="Inicio programado" type="datetime-local" :value="now()->format('Y-m-d\TH:i')" required />
        </div>
        <div class="form-actions"><a class="btn btn-ghost" href="{{ route('recorridos.index') }}">Cancelar</a><button class="btn">Programar</button></div>
    </form>
</div>
@endsection
