@extends('layouts.app')
@section('title', 'Registrar incidente')
@section('content')
<div class="page-head"><div><h1>Registrar incidente</h1><p>RF-022</p></div></div>
<div class="card" style="max-width: 800px">
    <form method="POST" action="{{ route('incidentes.store') }}" class="form">@csrf
        <div class="form-row">
            <x-campo name="tipo_incidente" label="Tipo" type="select" required>
                @foreach ($tipos as $t)<option @selected(old('tipo_incidente') === $t)>{{ $t }}</option>@endforeach
            </x-campo>
            <x-campo name="id_bus" label="Bus" type="select"><option value="">—</option>@foreach ($buses as $b)<option value="{{ $b->id_bus }}" @selected(old('id_bus') == $b->id_bus)>{{ $b->numero_interno }}</option>@endforeach</x-campo>
            <x-campo name="id_estacion" label="Estación" type="select"><option value="">—</option>@foreach ($estaciones as $e)<option value="{{ $e->id_estacion }}" @selected(old('id_estacion') == $e->id_estacion)>{{ $e->nombre }}</option>@endforeach</x-campo>
            <x-campo name="id_dispositivo" label="Dispositivo" type="select"><option value="">—</option>@foreach ($dispositivos as $d)<option value="{{ $d->id_dispositivo }}" @selected(old('id_dispositivo') == $d->id_dispositivo)>{{ $d->codigo_inventario }} ({{ $d->tipo }})</option>@endforeach</x-campo>
        </div>
        <x-campo name="descripcion" label="Descripción" type="textarea" required />
        <div class="form-actions"><a class="btn btn-ghost" href="{{ route('incidentes.index') }}">Cancelar</a><button class="btn">Registrar</button></div>
    </form>
</div>
@endsection
