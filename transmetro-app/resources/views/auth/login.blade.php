<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión · Transmetro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/transmetro.css') }}">
</head>
<body>
<div class="login-wrap">
    <div class="login">
        <div class="card">
            <div class="brand"><img class="brand-logo" src="{{ asset('img/logomuni.png') }}" alt="Municipalidad de Guatemala"> Transmetro</div>
            <p class="sub">Sistema de Gestión y Control</p>
            <form method="POST" action="{{ url('/login') }}" class="form">
                @csrf
                <x-campo name="usuario" label="Usuario" required autofocus autocomplete="username" />
                <x-campo name="password" label="Contraseña" type="password" required autocomplete="current-password" />
                <button class="btn" type="submit" style="justify-content:center">Ingresar</button>
            </form>
            @if (app()->environment('local'))
                <div class="demo-users">
                    Usuarios de prueba (contraseña <code>Transmetro2026</code>):
                    <code>admin</code>, <code>transporte</code>, <code>operador</code>, <code>piloto</code>, <code>guardia</code>
                </div>
            @endif
        </div>
    </div>
</div>
</body>
</html>
