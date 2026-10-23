<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Panel') · Transmetro</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/transmetro.css') }}">
</head>
<body>
@php
    $u = auth()->user();
    $pendientes = $u->puede('alertas') ? \App\Models\Alerta::where('estado', 'PENDIENTE')->count() : 0;
    $esActivo = fn ($patron) => str_contains($patron, '/') ? request()->is($patron) : request()->routeIs($patron);
    // Los 7 módulos del análisis de requerimientos, solo con las opciones que el rol puede usar (RN-015)
    $modulos = collect(config('transmetro.modulos'))
        ->map(function ($m) use ($u, $esActivo) {
            $m['opciones'] = array_values(array_filter($m['opciones'], fn ($o) => $u->puede($o[3])));
            $m['activo'] = collect($m['opciones'])->contains(fn ($o) => $esActivo($o[4]));
            return $m;
        })
        ->filter(fn ($m) => $m['opciones']);
    $moduloActual = $modulos->firstWhere('activo', true);
    $iniciales = mb_strtoupper(mb_substr($u->nombre, 0, 1).mb_substr($u->apellido, 0, 1));
@endphp
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand"><img class="brand-logo" src="{{ asset('img/logomuni.png') }}" alt="Municipalidad de Guatemala"> Transmetro</div>
        <nav aria-label="Menú principal">
            <a href="{{ route('dashboard') }}" class="nav-home {{ request()->routeIs('dashboard') ? 'active' : '' }}" @if(request()->routeIs('dashboard')) aria-current="page" @endif>
                <x-icono nombre="inicio" /> Panel general
            </a>
            <p class="nav-section">Módulos</p>
        @foreach ($modulos as $m)
            @php $tieneAlertas = $pendientes && collect($m['opciones'])->contains(fn ($o) => $o[1] === 'alertas.index'); @endphp
            {{-- Submenú desplegable: name="menu" hace que solo uno esté abierto a la vez --}}
            <details class="nav-group" name="menu" @if($m['activo']) open @endif>
                <summary class="nav-title" title="{{ $m['nombre'] }}">
                    <span class="nav-icon"><x-icono :nombre="$m['icono']" /></span>
                    <span class="nav-name"><span class="nav-num">Módulo {{ $m['numero'] }}</span>{{ $m['corto'] }}</span>
                    @if ($tieneAlertas)<span class="nav-count nav-count-group">{{ $pendientes }}</span>@endif
                    <span class="nav-chevron" aria-hidden="true"></span>
                </summary>
                <div class="nav-items">
                @foreach ($m['opciones'] as [$label, $ruta, $params, $permiso, $patron])
                    @php $activo = $esActivo($patron); @endphp
                    <a href="{{ route($ruta, $params) }}" class="nav-link {{ $activo ? 'active' : '' }}" @if($activo) aria-current="page" @endif>
                        {{ $label }}
                        @if ($ruta === 'alertas.index' && $pendientes)<span class="nav-count">{{ $pendientes }}</span>@endif
                    </a>
                @endforeach
                </div>
            </details>
        @endforeach
        </nav>
        <div class="nav-user">
            <span class="avatar">{{ $iniciales }}</span>
            <span class="nav-user-info"><strong>{{ $u->nombre_completo }}</strong><small>{{ ucwords(mb_strtolower(str_replace('_', ' ', $u->rol->nombre))) }}</small></span>
        </div>
    </aside>
    <div class="sidebar-backdrop" onclick="document.getElementById('sidebar').classList.remove('open')"></div>

    <main class="main">
        <div class="topbar">
            <button class="btn btn-ghost btn-sm menu-toggle" type="button" aria-controls="sidebar" onclick="document.getElementById('sidebar').classList.toggle('open')"><x-icono nombre="menu" :tam="16" /> Menú</button>
            <div class="topbar-title">
                @if ($moduloActual)
                    <span class="crumb">Módulo {{ $moduloActual['numero'] }}</span> {{ $moduloActual['nombre'] }}
                @else
                    Sistema de Gestión y Control
                @endif
            </div>
            <div class="user">
                <span class="user-name">{{ $u->nombre_completo }} · <span class="badge info">{{ str_replace('_', ' ', $u->rol->nombre) }}</span></span>
                <form method="POST" action="{{ route('logout') }}" class="inline-form">@csrf<button class="btn btn-ghost btn-sm"><x-icono nombre="salir" :tam="14" /> Cerrar sesión</button></form>
            </div>
        </div>

        @if (session('ok'))<div class="alert alert-ok" role="status">{{ session('ok') }}</div>@endif
        @if (session('aviso'))<div class="alert alert-warn" role="status">{{ session('aviso') }}</div>@endif
        @if (session('error'))<div class="alert alert-error" role="alert">{{ session('error') }}</div>@endif

        @yield('content')
    </main>
</div>
</body>
</html>
