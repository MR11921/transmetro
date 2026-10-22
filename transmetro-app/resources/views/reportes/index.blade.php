@extends('layouts.app')
@section('title', 'Reportes')
@section('content')
<div class="page-head"><div><h1>Reportes</h1><p>RF-023 · Consulta en pantalla o exportación CSV</p></div></div>
<div class="grid grid-3">
    @foreach ($tipos as $clave => $titulo)
        <a class="card" style="text-decoration:none" href="{{ route('reportes.show', $clave) }}">
            <h3>{{ $titulo }}</h3><small>Ver reporte →</small>
        </a>
    @endforeach
</div>
@endsection
