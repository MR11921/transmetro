@extends('layouts.app')
@section('title', 'Distancias')
@section('content')
<div class="page-head">
    <div><h1>Distancias</h1><p>Distancia entre estaciones consecutivas y total de cada línea</p></div>
</div>

<div class="stack">
@forelse ($lineas as $l)
    <section class="card">
        <div class="card-head">
            <h2><a href="{{ route('lineas.show', $l) }}">{{ $l->nombre }}</a> <x-estado :valor="$l->estado" /></h2>
            <span class="total-km">{{ number_format($l->distanciaTotal(), 2) }} km<small> en total</small></span>
        </div>
        @if ($l->estaciones->isEmpty())
            <p class="empty">La línea aún no tiene estaciones. <a href="{{ route('lineas.show', $l) }}">Agregar estaciones</a></p>
        @else
            <ol class="tramo">
            @foreach ($l->estaciones as $i => $e)
                <li>
                    @if ($i > 0)<span class="tramo-km">{{ $e->pivot->distancia_desde_anterior !== null ? number_format($e->pivot->distancia_desde_anterior, 2).' km' : 'Sin dato' }}</span>@endif
                    <span class="tramo-est"><span class="tramo-n">{{ $i + 1 }}</span>{{ $e->nombre }}</span>
                </li>
            @endforeach
            </ol>
            <small>{{ $l->municipalidad->nombre }} · {{ $l->estaciones->count() }} estaciones · Para editar una distancia, abra la línea.</small>
        @endif
    </section>
@empty
    <p class="card empty">No hay líneas registradas.</p>
@endforelse
</div>
@endsection
