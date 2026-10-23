@props(['nombre', 'tam' => 20])
@php
    // Íconos de línea (trazo 2px, 24×24) para menú, panel y tarjetas.
    $trazos = [
        'inicio' => '<path d="M3 11l9-7 9 7"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/>',
        'candado' => '<rect x="5" y="11" width="14" height="10"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
        'edificio' => '<path d="M3 21h18"/><path d="M5 21V10l7-5 7 5v11"/><path d="M9 21v-6h6v6"/><path d="M9 11h.01M15 11h.01"/>',
        'bus' => '<rect x="4" y="3" width="16" height="15"/><path d="M4 11h16"/><path d="M8 21v-3M16 21v-3"/><path d="M8 14.5h.01M16 14.5h.01"/>',
        'ticket' => '<path d="M3 7h18v3a2 2 0 0 0 0 4v3H3v-3a2 2 0 0 0 0-4z"/><path d="M14 7v10" stroke-dasharray="2 2"/>',
        'personas' => '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="9" r="2.5"/><path d="M16 14.2c2.9.4 5 2.7 5 5.8"/>',
        'monitor' => '<rect x="3" y="4" width="18" height="12"/><path d="M8 20h8M12 16v4"/><path d="M6 12l3-3 3 2 4-4"/>',
        'grafica' => '<path d="M4 20V4"/><path d="M4 20h16"/><path d="M8 16v-5M12 16V8M16 16v-3"/>',
        'alerta' => '<path d="M12 3l10 18H2z"/><path d="M12 10v4M12 17.5h.01"/>',
        'incidente' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 16.5h.01"/>',
        'ruta' => '<circle cx="6" cy="18" r="2"/><circle cx="18" cy="6" r="2"/><path d="M8 18h7a3 3 0 0 0 0-6H9a3 3 0 0 1 0-6h7"/>',
        'estacion' => '<path d="M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12z"/><circle cx="12" cy="9" r="2.5"/>',
        'entrada' => '<path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M14 3h7v18h-7"/>',
        'dispositivo' => '<rect x="7" y="3" width="10" height="18"/><path d="M11 17.5h2"/>',
        'linea' => '<circle cx="5" cy="12" r="2"/><circle cx="12" cy="12" r="2"/><circle cx="19" cy="12" r="2"/><path d="M7 12h3M14 12h3"/>',
        'mas' => '<path d="M12 5v14M5 12h14"/>',
        'flecha' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'salir' => '<path d="M14 17l5-5-5-5"/><path d="M19 12H9"/><path d="M10 3H4v18h6"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'reloj' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'buscar' => '<circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/>',
    ];
@endphp
<svg {{ $attributes->merge(['class' => 'icono']) }} width="{{ $tam }}" height="{{ $tam }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $trazos[$nombre] ?? '' !!}</svg>
