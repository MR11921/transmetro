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
    ],
];
