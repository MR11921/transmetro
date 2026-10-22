<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Acceso denegado · Transmetro</title><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap"><link rel="stylesheet" href="{{ asset('css/transmetro.css') }}"></head>
<body><div class="login-wrap"><div class="login"><div class="card" style="text-align:center">
<h1>403</h1><p class="mt-16">{{ $exception->getMessage() ?: 'No tiene permisos para acceder a este recurso.' }}</p>
<p class="mt-16"><a class="btn" href="{{ url('/') }}">Volver al panel</a></p>
</div></div></div></body></html>
