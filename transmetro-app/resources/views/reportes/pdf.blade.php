<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>{{ $titulo }} · Transmetro</title>
<style>
    @page { margin: 96px 32px 48px; }
    body { font-family: "DejaVu Sans", sans-serif; font-size: 9px; color: #1b1f4b; }
    header { position: fixed; top: -76px; left: 0; right: 0; height: 60px; border-bottom: 3px solid #83bd3f; }
    header img { position: absolute; left: 0; top: 0; width: 52px; height: 52px; }
    header .org { position: absolute; left: 64px; top: 6px; }
    header .org strong { display: block; font-size: 14px; letter-spacing: 3px; color: #030e9e; }
    header .org span { font-size: 8px; letter-spacing: 2px; color: #4a4f7a; text-transform: uppercase; }
    header .meta { position: absolute; right: 0; top: 8px; text-align: right; font-size: 8px; color: #4a4f7a; line-height: 1.5; }
    footer { position: fixed; bottom: -32px; left: 0; right: 0; height: 20px; border-top: 1px solid #d5d8ee; font-size: 8px; color: #7a7fa3; padding-top: 4px; }
    h1 { font-size: 15px; color: #030e9e; text-transform: uppercase; letter-spacing: 2px; margin: 0 0 4px; }
    .sub { font-size: 9px; color: #4a4f7a; margin: 0 0 12px; }
    table { width: 100%; border-collapse: collapse; }
    th { background: #030e9e; color: #fff; text-align: left; font-size: 8px; text-transform: uppercase; letter-spacing: 1px; padding: 6px; }
    td { padding: 5px 6px; border-bottom: 1px solid #d5d8ee; vertical-align: top; }
    tr:nth-child(even) td { background: #f4f5fb; }
    .empty { text-align: center; color: #4a4f7a; padding: 16px; }
</style>
</head>
<body>
<header>
    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('img/logomuni.png'))) }}" alt="">
    <div class="org"><strong>TRANSMETRO</strong><span>Sistema de Gestión y Control · Municipalidad de Guatemala</span></div>
    <div class="meta">Generado el {{ now()->format('d/m/Y H:i') }}<br>Por {{ auth()->user()->nombre_completo }}</div>
</header>
<footer>Reporte: {{ $titulo }}</footer>

<h1>{{ $titulo }}</h1>
<p class="sub">Período: {{ $desde->format('d/m/Y') }} al {{ $hasta->format('d/m/Y') }} · {{ count($filas) }} {{ count($filas) === 1 ? 'registro' : 'registros' }}</p>

<table>
    <thead><tr>@foreach ($columnas as $c)<th>{{ $c }}</th>@endforeach</tr></thead>
    <tbody>
    @forelse ($filas as $fila)
        <tr>@foreach ($fila as $v)<td>{{ is_string($v) ? str_replace('_', ' ', $v) : $v }}</td>@endforeach</tr>
    @empty
        <tr><td colspan="{{ count($columnas) }}" class="empty">Sin datos para el período.</td></tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
