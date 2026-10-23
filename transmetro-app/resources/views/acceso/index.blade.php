@extends('layouts.app')
@section('title', 'Control de acceso')
@section('content')
<div class="page-head"><div><h1>Control de acceso</h1><p>Todo ticket se valida antes de permitir el paso</p></div></div>
<div class="grid grid-2">
    <section class="card">
        <h2>Validar ticket</h2>
        <form method="POST" action="{{ route('acceso.validar') }}" class="form mt-16">@csrf
            <x-campo name="id_acceso" label="Acceso (torniquete / barrera)" type="select" required>
                @foreach ($accesos as $a)<option value="{{ $a->id_acceso }}" @selected(old('id_acceso') == $a->id_acceso)>{{ $a->estacion->nombre }} — {{ $a->nombre }}</option>@endforeach
            </x-campo>
            <x-campo name="tipo_movimiento" label="Movimiento" type="select" required>
                @foreach (['ENTRADA', 'SALIDA'] as $t)<option @selected(old('tipo_movimiento') === $t)>{{ $t }}</option>@endforeach
            </x-campo>
            <x-campo name="codigo_ticket" label="Código del ticket" required autofocus placeholder="TM-XXXXXXXX" />
            <button class="btn" style="justify-content:center">Validar</button>
        </form>
    </section>
    <section class="card">
        <div class="card-head"><h2>Movimientos recientes</h2>
            <form method="GET" class="actions">
                <select name="resultado" class="input input-inline" onchange="this.form.submit()" aria-label="Resultado">
                    <option value="">Todos</option>@foreach(['VALIDADO','RECHAZADO'] as $r)<option @selected(request('resultado') === $r)>{{ $r }}</option>@endforeach
                </select>
            </form>
        </div>
        <div class="table-wrap"><table class="table">
            <thead><tr><th>Hora</th><th>Estación / acceso</th><th>Ticket</th><th>Tipo</th><th>Resultado</th></tr></thead>
            <tbody>
            @forelse ($movimientos as $m)
                <tr>
                    <td><small>{{ $m->fecha_hora->format('d/m H:i:s') }}</small></td>
                    <td>{{ $m->estacion->nombre }}<br><small>{{ $m->acceso?->nombre }}</small></td>
                    <td>{{ $m->ticket?->codigo_ticket ?? '—' }}</td>
                    <td>{{ $m->tipo_movimiento }}</td>
                    <td><x-estado :valor="$m->resultado" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty">Sin movimientos.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        {{ $movimientos->links('partials.paginacion') }}
    </section>
</div>
@endsection
