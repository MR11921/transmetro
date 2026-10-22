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
    $menu = [
        'Operación' => [
            ['Panel general', 'dashboard', [], 'dashboard', 'dashboard'],
            ['Recorridos y TCP', 'recorridos.index', [], 'tcp', 'recorridos.*'],
            ['Alertas', 'alertas.index', [], 'alertas', 'alertas.*'],
            ['Incidentes', 'incidentes.index', [], 'incidentes', 'incidentes.*'],
            ['Dispositivos', 'catalogos.index', ['dispositivos'], 'dispositivos', 'catalogos/dispositivos*'],
        ],
        'Pasajeros' => [
            ['Terminal de autoservicio', 'tat', [], 'tat', 'tat'],
            ['Control de acceso', 'acceso.index', [], 'control_acceso', 'acceso.*'],
            ['Tickets', 'tickets.index', [], 'tickets', 'tickets.*'],
            ['Pasajeros', 'catalogos.index', ['pasajeros'], 'pasajeros', 'catalogos/pasajeros*'],
            ['Métodos de pago', 'catalogos.index', ['metodos_pago'], 'metodos_pago', 'catalogos/metodos_pago*'],
        ],
        'Infraestructura' => [
            ['Líneas', 'lineas.index', [], 'lineas', 'lineas.*'],
            ['Estaciones', 'catalogos.index', ['estaciones'], 'estaciones', 'catalogos/estaciones*'],
            ['Accesos', 'catalogos.index', ['accesos'], 'accesos', 'catalogos/accesos*'],
            ['Seguridad en accesos', 'seguridad.index', [], 'accesos', 'seguridad.*'],
            ['Municipalidades', 'catalogos.index', ['municipalidades'], 'municipalidades', 'catalogos/municipalidades*'],
        ],
        'Flota y personal' => [
            ['Buses', 'buses.index', [], 'buses', 'buses.*'],
            ['Parqueos', 'catalogos.index', ['parqueos'], 'parqueos', 'catalogos/parqueos*'],
            ['Pilotos', 'catalogos.index', ['pilotos'], 'pilotos', 'catalogos/pilotos*'],
            ['Guardias', 'catalogos.index', ['guardias'], 'guardias', 'catalogos/guardias*'],
            ['Operadores', 'operadores.index', [], 'operadores', 'operadores.*'],
        ],
        'Control' => [
            ['Reportes', 'reportes.index', [], 'reportes', 'reportes.*'],
            ['Usuarios', 'usuarios.index', [], 'usuarios', 'usuarios.*'],
            ['Auditoría', 'auditoria.index', [], 'auditoria', 'auditoria.*'],
        ],
    ];
@endphp
<div class="app">
    <aside class="sidebar" id="sidebar">
        <div class="brand"><img class="brand-logo" src="{{ asset('img/logomuni.png') }}" alt="Municipalidad de Guatemala"> Transmetro</div>
        <nav aria-label="Menú principal">
        @foreach ($menu as $grupo => $items)
            @php
                $visibles = array_filter($items, fn ($i) => $u->puede($i[3]));
                $esActivo = fn ($patron) => str_contains($patron, '/') ? request()->is($patron) : request()->routeIs($patron);
                $grupoActivo = collect($visibles)->contains(fn ($i) => $esActivo($i[4]));
                $tieneAlertas = $pendientes && collect($visibles)->contains(fn ($i) => $i[1] === 'alertas.index');
            @endphp
            @if ($visibles)
                {{-- Submenú desplegable: name="menu" hace que solo uno esté abierto a la vez --}}
                <details class="nav-group" name="menu" @if($grupoActivo) open @endif>
                    <summary class="nav-title">
                        <span>{{ $grupo }}</span>
                        @if ($tieneAlertas)<span class="nav-count nav-count-group">{{ $pendientes }}</span>@endif
                        <span class="nav-chevron" aria-hidden="true"></span>
                    </summary>
                    <div class="nav-items">
                    @foreach ($visibles as [$label, $ruta, $params, $modulo, $patron])
                        @php $activo = $esActivo($patron); @endphp
                        <a href="{{ route($ruta, $params) }}" class="nav-link {{ $activo ? 'active' : '' }}" @if($activo) aria-current="page" @endif>
                            {{ $label }}
                            @if ($ruta === 'alertas.index' && $pendientes)<span class="nav-count">{{ $pendientes }}</span>@endif
                        </a>
                    @endforeach
                    </div>
                </details>
            @endif
        @endforeach
        </nav>
    </aside>

    <main class="main">
        <div class="topbar">
            <button class="btn btn-ghost btn-sm menu-toggle" type="button" onclick="document.getElementById('sidebar').classList.toggle('open')">Menú</button>
            <div class="topbar-title">Sistema de Gestión y Control</div>
            <div class="user">
                <span>{{ $u->nombre_completo }} · <span class="badge info">{{ str_replace('_', ' ', $u->rol->nombre) }}</span></span>
                <form method="POST" action="{{ route('logout') }}" class="inline-form">@csrf<button class="btn btn-ghost btn-sm">Cerrar sesión</button></form>
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
