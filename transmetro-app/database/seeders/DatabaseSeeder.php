<?php

namespace Database\Seeders;

use App\Models;
use App\Services\ControlAcceso;
use App\Services\ControlOcupacion;
use App\Services\GestionFlota;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Datos de demostración. Contraseña de todos los usuarios: Transmetro2026
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesSeeder::class);

        // Usuarios, uno por rol
        $roles = Models\Rol::pluck('id_rol', 'nombre');
        foreach ([
            ['admin', 'Alan', 'Rodas', 'ADMINISTRADOR'],
            ['transporte', 'Lucía', 'Méndez', 'ADMINISTRADOR_TRANSPORTE'],
            ['operador', 'Carlos', 'Pérez', 'OPERADOR_ESTACION'],
            ['piloto', 'José', 'Ramírez', 'PILOTO'],
            ['guardia', 'Ana', 'López', 'GUARDIA'],
        ] as [$usuario, $nombre, $apellido, $rol]) {
            Models\Usuario::create([
                'id_rol' => $roles[$rol], 'nombre' => $nombre, 'apellido' => $apellido, 'usuario' => $usuario,
                'correo' => "{$usuario}@transmetro.test", 'password_hash' => Hash::make('Transmetro2026'),
            ]);
        }

        // Municipalidades
        $guate = Models\Municipalidad::create(['nombre' => 'Guatemala', 'departamento' => 'Guatemala']);
        $villaNueva = Models\Municipalidad::create(['nombre' => 'Villa Nueva', 'departamento' => 'Guatemala']);
        Models\Municipalidad::create(['nombre' => 'Mixco', 'departamento' => 'Guatemala']);

        // Estaciones
        $est = [];
        foreach ([
            ['Centra Sur', $villaNueva, 'Calzada Raúl Aguilar Batres, zona 12', 'S'],
            ['Las Charcas', $guate, 'Av. Petapa y 23 calle, zona 11', 'N'],
            ['El Trébol', $guate, 'Trébol, zona 8', 'N'],
            ['Plaza Barrios', $guate, '18 calle, zona 1', 'N'],
            ['Plaza Municipal', $guate, '21 calle, zona 1', 'N'],
            ['Hipódromo', $guate, 'Av. Simeón Cañas, zona 2', 'S'],
            ['Simeón Cañas', $guate, 'Av. Simeón Cañas, zona 2', 'N'],
            ['Reforma Obelisco', $guate, 'Av. Reforma, zona 10', 'N'],
        ] as [$nombre, $muni, $dir, $parqueo]) {
            $est[$nombre] = Models\Estacion::create(['nombre' => $nombre, 'id_municipalidad' => $muni->id_municipalidad, 'direccion' => $dir, 'tiene_parqueo' => $parqueo]);
        }

        // Líneas con estaciones ordenadas y distancias (km)
        $l12 = Models\Linea::create(['nombre' => 'Línea 12', 'id_municipalidad' => $guate->id_municipalidad, 'descripcion' => 'Centra Sur – Centro Histórico']);
        $l13 = Models\Linea::create(['nombre' => 'Línea 13', 'id_municipalidad' => $guate->id_municipalidad, 'descripcion' => 'Hipódromo – Reforma']);
        foreach ([['Centra Sur', null], ['Las Charcas', 3.20], ['El Trébol', 4.10], ['Plaza Barrios', 2.30], ['Plaza Municipal', 1.40]] as $i => [$n, $d]) {
            $l12->estaciones()->attach($est[$n]->id_estacion, ['orden_estacion' => $i + 1, 'distancia_desde_anterior' => $d]);
        }
        foreach ([['Hipódromo', null], ['Simeón Cañas', 1.10], ['El Trébol', 4.80], ['Reforma Obelisco', 3.60]] as $i => [$n, $d]) {
            $l13->estaciones()->attach($est[$n]->id_estacion, ['orden_estacion' => $i + 1, 'distancia_desde_anterior' => $d]);
        }

        // Guardias y accesos (RN-007: el acceso "Sur" de Plaza Barrios queda sin guardia a propósito)
        $guardias = collect([['Mario', 'Hernández'], ['Rosa', 'Castillo'], ['Pedro', 'Juárez'], ['Elena', 'Morales'], ['Luis', 'García'], ['Marta', 'Ortiz']])
            ->map(fn ($g) => Models\Guardia::create(['nombre' => $g[0], 'apellido' => $g[1], 'telefono' => '5'.rand(1000000, 9999999)]));
        $g = 0;
        foreach ($est as $nombre => $e) {
            foreach (['Norte' => 'TORNIQUETE', 'Sur' => 'BARRERA'] as $acc => $tipo) {
                if ($acc === 'Sur' && ! in_array($nombre, ['Centra Sur', 'El Trébol', 'Plaza Barrios'])) {
                    continue;
                }
                $acceso = Models\Acceso::create(['id_estacion' => $e->id_estacion, 'nombre' => "Acceso {$acc}", 'tipo' => $tipo]);
                if (! ($nombre === 'Plaza Barrios' && $acc === 'Sur')) {
                    $acceso->guardias()->attach($guardias[$g++ % $guardias->count()]->id_guardia, ['fecha_inicio' => now()->subMonth()]);
                }
                Models\Dispositivo::create(['id_estacion' => $e->id_estacion, 'codigo_inventario' => 'CA-'.str_pad((string) $acceso->id_acceso, 3, '0', STR_PAD_LEFT), 'tipo' => 'CONTROL_ACCESO', 'fecha_instalacion' => now()->subMonths(3)]);
            }
            Models\Dispositivo::create(['id_estacion' => $e->id_estacion, 'codigo_inventario' => 'TAT-'.str_pad((string) $e->id_estacion, 3, '0', STR_PAD_LEFT), 'tipo' => 'TAT', 'fecha_instalacion' => now()->subMonths(3)]);
        }

        // Operador asignado a una estación
        Models\OperadorEstacion::create(['id_usuario' => Models\Usuario::where('usuario', 'operador')->value('id_usuario'), 'id_estacion' => $est['El Trébol']->id_estacion, 'fecha_asignacion' => today()]);

        // Parqueos
        $parqueos = collect();
        foreach (['Centra Sur' => 'CS', 'Hipódromo' => 'HP'] as $nombre => $pref) {
            for ($i = 1; $i <= 6; $i++) {
                $parqueos->push(Models\Parqueo::create(['id_estacion' => $est[$nombre]->id_estacion, 'codigo' => "{$pref}-".str_pad((string) $i, 2, '0', STR_PAD_LEFT), 'ubicacion' => "Andén {$i}"]));
            }
        }

        // Buses: 6 en Línea 12 (rango 5–10) y 4 en Línea 13 (rango 4–8)
        $flota = app(GestionFlota::class);
        $buses = collect();
        for ($i = 1; $i <= 10; $i++) {
            $bus = $flota->registrarBus([
                'placa' => 'U-'.(100 + $i).'BCD', 'numero_interno' => 'TM-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'capacidad_maxima' => $i <= 6 ? 120 : 80,
            ], $parqueos[$i - 1]->id_parqueo);
            $flota->asignarLinea($bus, $i <= 6 ? $l12 : $l13);
            Models\Dispositivo::create(['id_bus' => $bus->id_bus, 'codigo_inventario' => 'TCP-'.str_pad((string) $i, 3, '0', STR_PAD_LEFT), 'tipo' => 'TCP', 'fecha_instalacion' => now()->subMonths(2)]);
            $buses->push($bus);
        }

        // Pilotos
        $pilotos = collect([
            ['José', 'Ramírez', 'Diversificado — Perito Contador'],
            ['María', 'Gómez', 'Diversificado — Bachiller en Computación'],
            ['Julio', 'Santos', 'Básico completo'],
            ['Andrea', 'Cifuentes', 'Licenciatura en curso — Administración'],
        ])->map(fn ($p) => Models\Piloto::create([
            'nombre' => $p[0], 'apellido' => $p[1], 'formacion_academica' => $p[2], 'telefono' => '4'.rand(1000000, 9999999),
            'correo' => strtolower($p[0]).'.'.strtolower($p[1]).'@transmetro.test', 'direccion' => 'Zona '.rand(1, 21).', Guatemala',
        ]));

        // Pasajeros, métodos de pago y tickets
        $efectivo = Models\MetodoPago::create(['nombre' => 'Efectivo', 'descripcion' => 'Pago en monedas en el TAT']);
        $prepago = Models\MetodoPago::create(['nombre' => 'Tarjeta prepago', 'descripcion' => 'Tarjeta recargable Transmetro']);
        Models\MetodoPago::create(['nombre' => 'Tarjeta bancaria', 'descripcion' => 'Débito o crédito sin contacto']);
        $pasajero = Models\Pasajero::create(['nombre' => 'Sofía', 'apellido' => 'Reyes', 'identificador' => 'TP-000123', 'tipo_pasajero' => 'ESTUDIANTE']);
        Models\Pasajero::create(['nombre' => 'Héctor', 'apellido' => 'Paz', 'identificador' => 'TP-000124', 'tipo_pasajero' => 'ADULTO_MAYOR']);

        $control = app(ControlAcceso::class);
        $acceso = Models\Acceso::where('id_estacion', $est['El Trébol']->id_estacion)->first();
        for ($i = 0; $i < 8; $i++) {
            $t = $control->emitirTicket($i % 2 ? $prepago->id_metodo_pago : $efectivo->id_metodo_pago, $i === 0 ? $pasajero->id_pasajero : null);
            if ($i < 5) {
                $control->validar($t->codigo_ticket, $acceso->id_acceso, 'ENTRADA');
            }
            if ($i < 2) {
                $control->validar($t->codigo_ticket, $acceso->id_acceso, 'SALIDA');
            }
        }
        $control->validar('TM-NOEXISTE', $acceso->id_acceso, 'ENTRADA');

        // Recorridos: uno finalizado, uno en curso y uno programado
        $tcp = app(ControlOcupacion::class);
        $fin = Models\Recorrido::create(['id_bus' => $buses[0]->id_bus, 'id_linea' => $l12->id_linea, 'id_piloto' => $pilotos[0]->id_piloto, 'fecha_inicio' => now()->subHours(3), 'estado' => 'EN_CURSO']);
        foreach ([[60, 0, 0], [70, 10, 140], [5, 40, 0], [0, 50, 0], [0, 35, 0]] as [$sube, $baja, $espera]) {
            $tcp->registrarParada($fin->fresh(), $sube, $baja, $espera);
        }
        $fin->update(['estado' => 'FINALIZADO', 'fecha_fin' => now()->subHours(2)]);

        $curso = Models\Recorrido::create(['id_bus' => $buses[6]->id_bus, 'id_linea' => $l13->id_linea, 'id_piloto' => $pilotos[1]->id_piloto, 'fecha_inicio' => now()->subMinutes(25), 'estado' => 'EN_CURSO']);
        $tcp->registrarParada($curso->fresh(), 12, 0);
        $tcp->registrarParada($curso->fresh(), 40, 2);

        Models\Recorrido::create(['id_bus' => $buses[1]->id_bus, 'id_linea' => $l12->id_linea, 'id_piloto' => $pilotos[2]->id_piloto, 'fecha_inicio' => now()->addMinutes(30), 'estado' => 'PROGRAMADO']);

        // Un dispositivo pierde comunicación (genera alerta automática) e incidente asociado
        $tat = Models\Dispositivo::where('codigo_inventario', 'TAT-004')->first();
        $tat->update(['estado' => 'DESCONECTADO']);
        Models\Incidente::create([
            'id_estacion' => $tat->id_estacion, 'id_dispositivo' => $tat->id_dispositivo,
            'id_usuario_registro' => Models\Usuario::where('usuario', 'operador')->value('id_usuario'),
            'tipo_incidente' => 'DISPOSITIVO', 'descripcion' => 'El TAT no responde; se habilitó venta manual temporal.', 'fecha_hora' => now()->subMinutes(10),
        ]);

        DB::table('auditoria')->insert([
            'id_usuario' => Models\Usuario::where('usuario', 'admin')->value('id_usuario'),
            'modulo' => 'Sistema', 'accion' => 'CARGA_INICIAL', 'detalle' => 'Datos de demostración cargados por el seeder.', 'fecha_hora' => now(),
        ]);
    }
}
