<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

/** Datos iniciales del script Oracle (sección 21). */
class RolesSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'ADMINISTRADOR' => 'Administración general del sistema',
            'ADMINISTRADOR_TRANSPORTE' => 'Gestión de la operación de transporte',
            'OPERADOR_ESTACION' => 'Supervisión de estaciones',
            'PILOTO' => 'Operación de unidades de transporte',
            'GUARDIA' => 'Control y seguridad de accesos',
        ] as $nombre => $descripcion) {
            Rol::firstOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);
        }
    }
}
