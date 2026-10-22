<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Alerta;
use App\Models\ControlPasajero;
use App\Models\Recorrido;
use Illuminate\Support\Facades\DB;

/**
 * Terminal de Control de Pasajeros (TCP) en el bus.
 * RF-015 abordajes/descensos · RF-016 ocupación · RF-017/018 alertas.
 * RN-010 ocupación = anterior − descienden + abordan.
 * RN-011 demanda ≥ 150 % de la capacidad → alerta de alta demanda.
 * RN-012 ocupación < 25 % → 5 minutos adicionales de espera en la estación.
 */
class ControlOcupacion
{
    /**
     * @param  int  $enEspera  pasajeros que quedaron en el andén sin poder abordar
     * @return array{control: ControlPasajero, alertas: Alerta[]}
     */
    public function registrarParada(Recorrido $recorrido, int $abordan, int $descienden, int $enEspera = 0): array
    {
        if ($recorrido->estado !== 'EN_CURSO') {
            throw new ReglaNegocioException('Solo se pueden registrar pasajeros en recorridos en curso.');
        }

        if ($abordan < 0 || $descienden < 0 || $enEspera < 0) {
            throw new ReglaNegocioException('Las cantidades de pasajeros no pueden ser negativas.');
        }

        $estacion = $recorrido->siguienteEstacion();

        if (! $estacion) {
            throw new ReglaNegocioException('El recorrido ya visitó todas las estaciones de la línea.');
        }

        $capacidad = $recorrido->bus->capacidad_maxima;
        $anterior = $recorrido->ocupacionActual();

        if ($descienden > $anterior) {
            throw new ReglaNegocioException("No pueden descender {$descienden} pasajeros: solo hay {$anterior} a bordo.");
        }

        $trasDescenso = $anterior - $descienden;

        if ($trasDescenso + $abordan > $capacidad) {
            $libres = $capacidad - $trasDescenso;
            throw new ReglaNegocioException("Capacidad excedida: solo pueden abordar {$libres} pasajeros (capacidad {$capacidad}). Registre el resto como pasajeros en espera.");
        }

        return DB::transaction(function () use ($recorrido, $estacion, $abordan, $descienden, $enEspera, $capacidad, $trasDescenso) {
            $ocupacion = $trasDescenso + $abordan;

            $control = ControlPasajero::create([
                'id_recorrido' => $recorrido->id_recorrido,
                'id_estacion' => $estacion->id_estacion,
                'pasajeros_abordan' => $abordan,
                'pasajeros_descienden' => $descienden,
                'ocupacion_calculada' => $ocupacion,
                'fecha_hora' => now(),
            ]);

            $alertas = [];
            $demanda = $ocupacion + $enEspera;
            $pctDemanda = round($demanda * 100 / $capacidad, 1);
            $pctOcupacion = round($ocupacion * 100 / $capacidad, 1);

            if ($pctDemanda >= config('transmetro.umbral_alta_demanda')) {
                $alertas[] = $this->alerta($recorrido, $estacion->id_estacion, 'ALTA_DEMANDA', 'ALTA',
                    "Demanda de {$demanda} pasajeros ({$pctDemanda} % de la capacidad de {$capacidad}) en {$estacion->nombre}. Se solicita otra unidad.");
            }

            if ($pctOcupacion < config('transmetro.umbral_baja_ocupacion')) {
                $minutos = config('transmetro.minutos_espera_adicional');
                $alertas[] = $this->alerta($recorrido, $estacion->id_estacion, 'ESPERA_ADICIONAL', 'BAJA',
                    "Ocupación de {$pctOcupacion} % (< ".config('transmetro.umbral_baja_ocupacion')." %) en {$estacion->nombre}. Se aplican {$minutos} minutos adicionales de espera.");
            }

            Auditor::registrar('TCP', 'REGISTRAR_PARADA', 'control_pasajeros', $control->id_control,
                "Recorrido {$recorrido->id_recorrido}, {$estacion->nombre}: +{$abordan} / −{$descienden} → {$ocupacion}");

            return ['control' => $control, 'alertas' => $alertas];
        });
    }

    private function alerta(Recorrido $recorrido, int $idEstacion, string $tipo, string $nivel, string $descripcion): Alerta
    {
        return Alerta::create([
            'id_bus' => $recorrido->id_bus,
            'id_estacion' => $idEstacion,
            'id_recorrido' => $recorrido->id_recorrido,
            'tipo_alerta' => $tipo,
            'nivel' => $nivel,
            'descripcion' => $descripcion,
            'fecha_hora' => now(),
            // La espera adicional es un registro operativo que se aplica de inmediato.
            'estado' => $tipo === 'ESPERA_ADICIONAL' ? 'ATENDIDA' : 'PENDIENTE',
        ]);
    }
}
