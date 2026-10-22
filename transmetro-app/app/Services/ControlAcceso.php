<?php

namespace App\Services;

use App\Exceptions\ReglaNegocioException;
use App\Models\Acceso;
use App\Models\MetodoPago;
use App\Models\MovimientoPasajero;
use App\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Emisión de tickets en el Terminal de Autoservicio (RF-011, RF-012)
 * y validación de entradas/salidas en los accesos (RF-013, RF-014, RN-013).
 */
class ControlAcceso
{
    public function emitirTicket(int $idMetodoPago, ?int $idPasajero = null, ?float $valor = null): Ticket
    {
        $metodo = MetodoPago::findOrFail($idMetodoPago);

        if ($metodo->estado !== 'ACTIVO') {
            throw new ReglaNegocioException("El método de pago {$metodo->nombre} no está activo.");
        }

        $ticket = Ticket::create([
            'id_pasajero' => $idPasajero,
            'id_metodo_pago' => $metodo->id_metodo_pago,
            'codigo_ticket' => $this->generarCodigo(),
            'fecha_emision' => now(),
            'valor' => $valor ?? config('transmetro.valor_ticket'),
            'estado' => 'VALIDO',
        ]);

        Auditor::registrar('TAT', 'EMITIR_TICKET', 'tickets', $ticket->id_ticket, "Ticket {$ticket->codigo_ticket} ({$metodo->nombre}, Q{$ticket->valor})");

        return $ticket;
    }

    /**
     * Valida el ticket y registra el movimiento (validado o rechazado).
     * ENTRADA: el ticket debe estar VALIDO y pasa a UTILIZADO.
     * SALIDA: el ticket debe haber registrado una entrada validada.
     *
     * @return array{autorizado: bool, mensaje: string, movimiento: MovimientoPasajero}
     */
    public function validar(string $codigo, int $idAcceso, string $tipo): array
    {
        $acceso = Acceso::with('estacion')->findOrFail($idAcceso);
        $ticket = Ticket::where('codigo_ticket', strtoupper(trim($codigo)))->first();

        [$autorizado, $mensaje] = $this->evaluar($ticket, $acceso, $tipo);

        return DB::transaction(function () use ($ticket, $acceso, $tipo, $autorizado, $mensaje) {
            if ($autorizado && $tipo === 'ENTRADA') {
                $ticket->update(['estado' => 'UTILIZADO']);
            }

            $movimiento = MovimientoPasajero::create([
                'id_pasajero' => $ticket?->id_pasajero,
                'id_ticket' => $ticket?->id_ticket,
                'id_estacion' => $acceso->id_estacion,
                'id_acceso' => $acceso->id_acceso,
                'tipo_movimiento' => $tipo,
                'fecha_hora' => now(),
                'resultado' => $autorizado ? 'VALIDADO' : 'RECHAZADO',
            ]);

            return ['autorizado' => $autorizado, 'mensaje' => $mensaje, 'movimiento' => $movimiento];
        });
    }

    /** @return array{0: bool, 1: string} */
    private function evaluar(?Ticket $ticket, Acceso $acceso, string $tipo): array
    {
        if ($acceso->estado !== 'ACTIVO' || $acceso->estacion->estado !== 'ACTIVA') {
            return [false, 'El acceso o la estación no se encuentran activos.'];
        }

        if (! $ticket) {
            return [false, 'Ticket no encontrado.'];
        }

        if ($tipo === 'ENTRADA') {
            return $ticket->estado === 'VALIDO'
                ? [true, 'Acceso autorizado. Ticket marcado como utilizado.']
                : [false, "Ticket {$ticket->estado}: no permite el ingreso."];
        }

        $entro = $ticket->movimientos()->where('tipo_movimiento', 'ENTRADA')->where('resultado', 'VALIDADO')->exists();
        $salio = $ticket->movimientos()->where('tipo_movimiento', 'SALIDA')->where('resultado', 'VALIDADO')->exists();

        if (! $entro) {
            return [false, 'El ticket no registra una entrada validada.'];
        }

        return $salio ? [false, 'El ticket ya registró su salida.'] : [true, 'Salida registrada.'];
    }

    private function generarCodigo(): string
    {
        do {
            $codigo = 'TM-'.strtoupper(Str::random(8));
        } while (Ticket::where('codigo_ticket', $codigo)->exists());

        return $codigo;
    }
}
