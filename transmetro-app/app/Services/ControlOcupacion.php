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
 * Bus lleno (100 %) → alerta para que el operador lo sepa aunque no llegue al 150 %.
 * Si quieren abordar más pasajeros de los que caben, abordan hasta llenar y el resto
 * queda en el andén y cuenta para la demanda.
 */
class ControlOcupacion
{
    /**
     * @param  int  $enEspera  pasajeros que quedaron en el andén sin poder abordar
     * @return array{control: ControlPasajero, alertas: Alerta[], sin_abordar: int}
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

        // Nunca se supera la capacidad: quienes no caben se quedan en el andén.
        $sinAbordar = max(0, $trasDescenso + $abordan - $capacidad);
        $abordan -= $sinAbordar;
        $enEspera += $sinAbordar;
        $esUltima = $recorrido->linea->estaciones->last()?->id_estacion === $estacion->id_estacion;

        return DB::transaction(function () use ($recorrido, $estacion, $abordan, $descienden, $enEspera, $capacidad, $trasDescenso, $sinAbordar, $esUltima) {
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
            } elseif ($ocupacion >= $capacidad) {
                $quedan = $enEspera > 0 ? " Quedaron {$enEspera} pasajeros en el andén." : '';
                $alertas[] = $this->alerta($recorrido, $estacion->id_estacion, 'BUS_LLENO', 'MEDIA',
                    "El bus va lleno ({$ocupacion} de {$capacidad} pasajeros) al salir de {$estacion->nombre}.{$quedan}");
            }

            // En la última estación todos descienden; no hay más paradas donde esperar.
            if (! $esUltima && $pctOcupacion < config('transmetro.umbral_baja_ocupacion')) {
                $minutos = config('transmetro.minutos_espera_adicional');
                $alertas[] = $this->alerta($recorrido, $estacion->id_estacion, 'ESPERA_ADICIONAL', 'BAJA',
                    "Ocupación de {$pctOcupacion} % (< ".config('transmetro.umbral_baja_ocupacion')." %) en {$estacion->nombre}. Se aplican {$minutos} minutos adicionales de espera.");
            }

            Auditor::registrar('TCP', 'REGISTRAR_PARADA', 'control_pasajeros', $control->id_control,
                "Recorrido {$recorrido->id_recorrido}, {$estacion->nombre}: +{$abordan} / −{$descienden} → {$ocupacion}");

            return ['control' => $control, 'alertas' => $alertas, 'sin_abordar' => $sinAbordar];
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
            'estado' => 'PENDIENTE',
        ]);
    }
}
