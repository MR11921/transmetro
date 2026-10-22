<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Bus;
use App\Models\BusLinea;
use App\Models\BusParqueo;
use App\Models\Linea;
use App\Models\Parqueo;
use Illuminate\Support\Facades\DB;

/**
 * Reglas de asignación de buses a líneas y parqueos.
 * RN-004 (cantidad de buses), RN-005 (una línea por bus), RN-006 (parqueo obligatorio con historial).
 */
class GestionFlota
{
    /** Crea un bus junto con su parqueo inicial (RN-006). */
    public function registrarBus(array $datos, int $idParqueo): Bus
    {
        return DB::transaction(function () use ($datos, $idParqueo) {
            $bus = Bus::create($datos);
            $this->cambiarParqueo($bus, $idParqueo);

            return $bus;
        });
    }

    /**
     * RN-005: cierra la asignación vigente antes de abrir la nueva.
     * RN-004: la línea destino no puede superar el doble de su número de estaciones.
     */
    public function asignarLinea(Bus $bus, Linea $linea): void
    {
        if ($bus->estado !== 'ACTIVO') {
            throw new ReglaNegocioException('Solo los buses activos pueden asignarse a una línea.');
        }

        if ($linea->estado !== 'ACTIVA') {
            throw new ReglaNegocioException('La línea seleccionada no está activa.');
        }

        $actual = $bus->lineaActual();

        if ($actual?->id_linea === $linea->id_linea) {
            throw new ReglaNegocioException("El bus ya está asignado a la línea {$linea->nombre}.");
        }

        if ($linea->minimoBuses() === 0) {
            throw new ReglaNegocioException('La línea no tiene estaciones; no se pueden calcular sus buses permitidos (RN-004).');
        }

        if ($linea->buses()->count() >= $linea->maximoBuses()) {
            throw new ReglaNegocioException("RN-004: la línea {$linea->nombre} ya tiene el máximo de {$linea->maximoBuses()} buses (doble de sus estaciones).");
        }

        if ($bus->recorridoEnCurso()) {
            throw new ReglaNegocioException('El bus tiene un recorrido en curso; finalícelo antes de cambiar de línea.');
        }

        DB::transaction(function () use ($bus, $linea, $actual) {
            $this->cerrarLineaVigente($bus);
            BusLinea::create(['id_bus' => $bus->id_bus, 'id_linea' => $linea->id_linea, 'fecha_inicio' => now()]);

            Auditor::registrar('Buses', 'ASIGNAR_LINEA', 'bus_linea', $bus->id_bus,
                "Bus {$bus->numero_interno}: ".($actual?->nombre ?? 'sin línea')." → {$linea->nombre}");
        });
    }

    public function liberarLinea(Bus $bus): void
    {
        $actual = $bus->lineaActual();

        if (! $actual) {
            throw new ReglaNegocioException('El bus no tiene línea asignada.');
        }

        if ($bus->recorridoEnCurso()) {
            throw new ReglaNegocioException('El bus tiene un recorrido en curso.');
        }

        $this->cerrarLineaVigente($bus);
        Auditor::registrar('Buses', 'LIBERAR_LINEA', 'bus_linea', $bus->id_bus, "Bus {$bus->numero_interno} retirado de {$actual->nombre}");
    }

    /** RN-006: el bus cambia de parqueo conservando el historial; nunca queda sin uno. */
    public function cambiarParqueo(Bus $bus, int $idParqueo): void
    {
        $parqueo = Parqueo::findOrFail($idParqueo);
        $actual = $bus->parqueoActual();

        if ($actual?->id_parqueo === $parqueo->id_parqueo) {
            throw new ReglaNegocioException("El bus ya se encuentra en el parqueo {$parqueo->codigo}.");
        }

        if ($parqueo->estado !== 'DISPONIBLE') {
            throw new ReglaNegocioException("El parqueo {$parqueo->codigo} no está disponible ({$parqueo->estado}).");
        }

        DB::transaction(function () use ($bus, $parqueo, $actual) {
            if ($actual) {
                BusParqueo::where('id_bus', $bus->id_bus)->whereNull('fecha_fin')->update(['fecha_fin' => now()]);
                $actual->update(['estado' => 'DISPONIBLE']);
            }

            BusParqueo::create(['id_bus' => $bus->id_bus, 'id_parqueo' => $parqueo->id_parqueo, 'fecha_inicio' => now()]);
            $parqueo->update(['estado' => 'OCUPADO']);

            Auditor::registrar('Parqueos', 'CAMBIAR_PARQUEO', 'bus_parqueo', $bus->id_bus,
                "Bus {$bus->numero_interno}: ".($actual?->codigo ?? 'sin parqueo')." → {$parqueo->codigo}");
        });
    }

    private function cerrarLineaVigente(Bus $bus): void
    {
        BusLinea::where('id_bus', $bus->id_bus)->whereNull('fecha_fin')->update(['fecha_fin' => now()]);
    }
}
