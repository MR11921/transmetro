@extends('layouts.app')
@section('title', 'Terminal de autoservicio')
@section('content')
<div class="page-head"><div><h1>Terminal de Autoservicio (TAT)</h1><p>RF-012 · Adquisición de tickets — valor Q{{ number_format(config('transmetro.valor_ticket'), 2) }}</p></div></div>
<div class="grid grid-2">
    <section class="card">
        <h2>Comprar ticket</h2>
        <form method="POST" action="{{ route('tat.emitir') }}" class="form mt-16">@csrf
            <x-campo name="id_metodo_pago" label="Método de pago" type="select" required>
                @foreach ($metodos as $m)<option value="{{ $m->id_metodo_pago }}" @selected(old('id_metodo_pago') == $m->id_metodo_pago)>{{ $m->nombre }}</option>@endforeach
            </x-campo>
            <x-campo name="id_pasajero" label="Pasajero registrado (opcional)" type="select">
                <option value="">Pasajero anónimo</option>
                @foreach ($pasajeros as $p)<option value="{{ $p->id_pasajero }}">{{ $p->nombre }} {{ $p->apellido }} — {{ $p->identificador }}</option>@endforeach
            </x-campo>
            <x-campo name="cantidad" label="Cantidad" type="number" min="1" max="10" value="1" required />
            <button class="btn" style="justify-content:center">Emitir ticket</button>
        </form>
    </section>
    <section class="card">
        <h2>Último ticket emitido</h2>
        @if ($ultimo)
            <div class="ticket mt-16">
                <small style="color:inherit">TRANSMETRO · {{ $ultimo->fecha_emision->format('d/m/Y H:i') }}</small>
                <div class="code">{{ $ultimo->codigo_ticket }}</div>
                <div>Q{{ number_format($ultimo->valor, 2) }} · {{ $ultimo->metodoPago->nombre }}</div>
            </div>
            <p class="mt-16 muted">Use este código en <a href="{{ route('acceso.index') }}">Control de acceso</a> para validar la entrada.</p>
        @else
            <p class="empty">Aún no se emitió ningún ticket en esta sesión.</p>
        @endif
    </section>
</div>
@endsection
