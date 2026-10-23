<?php

/*
| Parámetros del Sistema de Gestión y Control de Transmetro.
| Los umbrales corresponden a las reglas de negocio RN-011 y RN-012.
*/

$admin = 'ADMINISTRADOR';
$transporte = 'ADMINISTRADOR_TRANSPORTE';
$operador = 'OPERADOR_ESTACION';
$piloto = 'PILOTO';
$guardia = 'GUARDIA';

return [

    'umbral_alta_demanda' => 150,   // % de la capacidad (RN-011)
    'umbral_baja_ocupacion' => 25,  // % de la capacidad (RN-012)
    'minutos_espera_adicional' => 5,
    'valor_ticket' => 5.00,

    'roles' => [$admin, $transporte, $operador, $piloto, $guardia],

    /*
    | RN-015: módulos que puede usar cada rol. '*' = todos los roles.
    */
    'permisos' => [
        'dashboard' => ['*'],
        'usuarios' => [$admin],
        'auditoria' => [$admin],
        'municipalidades' => [$admin, $transporte],
        'lineas' => [$admin, $transporte],
        'estaciones' => [$admin, $transporte],
        'accesos' => [$admin, $transporte],
        'guardias' => [$admin, $transporte],
        'buses' => [$admin, $transporte],
        'parqueos' => [$admin, $transporte],
        'pilotos' => [$admin, $transporte],
        'operadores' => [$admin, $transporte],
        'metodos_pago' => [$admin, $transporte],
        'pasajeros' => [$admin, $transporte, $operador],
        'tickets' => [$admin, $transporte, $operador],
        'tat' => [$admin, $transporte, $operador],
        'control_acceso' => [$admin, $transporte, $operador, $guardia],
        'recorridos' => [$admin, $transporte],
        'tcp' => [$admin, $transporte, $piloto],
        'dispositivos' => [$admin, $transporte, $operador],
        'alertas' => [$admin, $transporte, $operador],
        'incidentes' => [$admin, $transporte, $operador, $piloto, $guardia],
        'reportes' => [$admin, $transporte],
        'consulta' => [$admin, $transporte, $operador],
        'ocupacion' => [$admin, $transporte, $operador, $piloto],
    ],

    /*
    | Los 7 módulos del Análisis de Requerimientos (6.3). Alimentan el menú lateral,
    | el panel general y la matriz de roles. Cada opción: [etiqueta, ruta, parámetros,
    | permiso, patrón de ruta activa, requisitos].
    */
    'modulos' => [
        [
            'numero' => 1, 'nombre' => 'Autenticación y control de acceso', 'corto' => 'Accesos al sistema',
            'icono' => 'candado', 'descripcion' => 'Usuarios, roles y permisos de la plataforma.',
            'opciones' => [
                ['Usuarios', 'usuarios.index', [], 'usuarios', 'usuarios.*', 'RF-001'],
                ['Roles y permisos', 'roles.index', [], 'usuarios', 'roles.*', 'RF-001 · RF-002'],
            ],
        ],
        [
            'numero' => 2, 'nombre' => 'Gestión administrativa', 'corto' => 'Administración',
            'icono' => 'edificio', 'descripcion' => 'Municipalidades, líneas, estaciones y la seguridad de sus accesos.',
            'opciones' => [
                ['Municipalidades', 'catalogos.index', ['municipalidades'], 'municipalidades', 'catalogos/municipalidades*', 'RF-003'],
                ['Líneas', 'lineas.index', [], 'lineas', 'lineas.*', 'RF-004'],
                ['Estaciones', 'catalogos.index', ['estaciones'], 'estaciones', 'catalogos/estaciones*', 'RF-005'],
                ['Accesos de estación', 'catalogos.index', ['accesos'], 'accesos', 'catalogos/accesos*', 'RF-006'],
                ['Guardias', 'catalogos.index', ['guardias'], 'guardias', 'catalogos/guardias*', 'RF-006'],
                ['Seguridad en accesos', 'seguridad.index', [], 'accesos', 'seguridad.*', 'RF-006'],
            ],
        ],
        [
            'numero' => 3, 'nombre' => 'Infraestructura y transporte', 'corto' => 'Flota y personal',
            'icono' => 'bus', 'descripcion' => 'Buses, parqueos, pilotos y operadores de estación.',
            'opciones' => [
                ['Buses', 'buses.index', [], 'buses', 'buses.*', 'RF-007'],
                ['Parqueos', 'catalogos.index', ['parqueos'], 'parqueos', 'catalogos/parqueos*', 'RF-008'],
                ['Pilotos', 'catalogos.index', ['pilotos'], 'pilotos', 'catalogos/pilotos*', 'RF-009'],
                ['Operadores de estación', 'operadores.index', [], 'operadores', 'operadores.*', 'RF-010'],
            ],
        ],
        [
            'numero' => 4, 'nombre' => 'Tickets y acceso', 'corto' => 'Tickets',
            'icono' => 'ticket', 'descripcion' => 'Venta de tickets, métodos de pago y validación de ingreso.',
            'opciones' => [
                ['Terminal de autoservicio', 'tat', [], 'tat', 'tat', 'RF-012'],
                ['Tickets', 'tickets.index', [], 'tickets', 'tickets.*', 'RF-011'],
                ['Métodos de pago', 'catalogos.index', ['metodos_pago'], 'metodos_pago', 'catalogos/metodos_pago*', 'RF-011'],
                ['Control de acceso', 'acceso.index', [], 'control_acceso', 'acceso.*', 'RF-013 · RF-014'],
                ['Pasajeros', 'catalogos.index', ['pasajeros'], 'pasajeros', 'catalogos/pasajeros*', 'RF-013'],
            ],
        ],
        [
            'numero' => 5, 'nombre' => 'Pasajeros y ocupación', 'corto' => 'Ocupación',
            'icono' => 'personas', 'descripcion' => 'Abordajes, ocupación de buses, demanda y alertas.',
            'opciones' => [
                ['Ocupación y TCP', 'ocupacion.index', [], 'ocupacion', 'ocupacion.*', 'RF-015 · RF-016 · RF-018'],
                ['Alertas', 'alertas.index', [], 'alertas', 'alertas.*', 'RF-017'],
            ],
        ],
        [
            'numero' => 6, 'nombre' => 'Operación y monitoreo', 'corto' => 'Operación',
            'icono' => 'monitor', 'descripcion' => 'Recorridos, distancias, dispositivos e incidentes.',
            'opciones' => [
                ['Recorridos', 'recorridos.index', [], 'tcp', 'recorridos.*', 'RF-019'],
                ['Distancias', 'distancias.index', [], 'lineas', 'distancias.*', 'RF-020'],
                ['Dispositivos', 'catalogos.index', ['dispositivos'], 'dispositivos', 'catalogos/dispositivos*', 'RF-021'],
                ['Incidentes', 'incidentes.index', [], 'incidentes', 'incidentes.*', 'RF-022'],
            ],
        ],
        [
            'numero' => 7, 'nombre' => 'Reportes y control', 'corto' => 'Reportes',
            'icono' => 'grafica', 'descripcion' => 'Reportes, auditoría y consulta integrada del servicio.',
            'opciones' => [
                ['Reportes', 'reportes.index', [], 'reportes', 'reportes.*', 'RF-023'],
                ['Auditoría', 'auditoria.index', [], 'auditoria', 'auditoria.*', 'RF-024'],
                ['Consulta integral', 'consulta.index', [], 'consulta', 'consulta.*', 'RF-025'],
            ],
        ],
    ],
];
