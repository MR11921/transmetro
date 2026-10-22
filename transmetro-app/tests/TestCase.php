<?php

namespace Tests;

use App\Models;
use App\Services\GestionFlota;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
    }

    protected function usuario(string $rol = 'ADMINISTRADOR', array $datos = []): Models\Usuario
    {
        static $n = 0;
        $n++;

        return Models\Usuario::create([
            'id_rol' => Models\Rol::where('nombre', $rol)->value('id_rol'),
            'nombre' => 'Prueba', 'apellido' => $rol, 'usuario' => "u{$n}", 'correo' => "u{$n}@transmetro.test",
            'password_hash' => Hash::make('Clave1234'),
            ...$datos,
        ]);
    }

    /** Línea con N estaciones consecutivas separadas 2 km. */
    protected function linea(int $estaciones = 3, string $nombre = 'Línea Prueba'): Models\Linea
    {
        $muni = Models\Municipalidad::firstOrCreate(['nombre' => 'Guatemala']);
        $linea = Models\Linea::create(['nombre' => $nombre, 'id_municipalidad' => $muni->id_municipalidad]);

        for ($i = 1; $i <= $estaciones; $i++) {
            $e = Models\Estacion::create(['nombre' => "{$nombre} E{$i}", 'id_municipalidad' => $muni->id_municipalidad]);
            $linea->estaciones()->attach($e->id_estacion, ['orden_estacion' => $i, 'distancia_desde_anterior' => $i === 1 ? null : 2]);
        }

        return $linea;
    }

    protected function parqueo(): Models\Parqueo
    {
        static $n = 0;
        $n++;
        $muni = Models\Municipalidad::firstOrCreate(['nombre' => 'Guatemala']);
        $estacion = Models\Estacion::firstOrCreate(['nombre' => 'Estación Parqueo'], ['id_municipalidad' => $muni->id_municipalidad]);

        return Models\Parqueo::create(['id_estacion' => $estacion->id_estacion, 'codigo' => "P-{$n}"]);
    }

    protected function bus(int $capacidad = 100, ?Models\Linea $linea = null): Models\Bus
    {
        static $n = 0;
        $n++;
        $flota = app(GestionFlota::class);
        $bus = $flota->registrarBus(['placa' => "P-{$n}", 'numero_interno' => "B-{$n}", 'capacidad_maxima' => $capacidad], $this->parqueo()->id_parqueo);

        if ($linea) {
            $flota->asignarLinea($bus, $linea);
        }

        return $bus->refresh();
    }

    protected function recorridoEnCurso(int $capacidad = 100, int $estaciones = 4): Models\Recorrido
    {
        $linea = $this->linea($estaciones, 'Línea R'.uniqid());
        $bus = $this->bus($capacidad, $linea);
        $piloto = Models\Piloto::create(['nombre' => 'Piloto', 'apellido' => 'Prueba']);

        return Models\Recorrido::create([
            'id_bus' => $bus->id_bus, 'id_linea' => $linea->id_linea, 'id_piloto' => $piloto->id_piloto,
            'fecha_inicio' => now(), 'estado' => 'EN_CURSO',
        ]);
    }
}
