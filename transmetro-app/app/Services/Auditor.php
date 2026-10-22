<?php

namespace App\Services;

use App\Models\Auditoria;
use Illuminate\Support\Facades\Auth;

/** RF-024 / RN-014: registro de operaciones críticas. */
class Auditor
{
    public static function registrar(string $modulo, string $accion, ?string $tabla = null, ?int $idRegistro = null, ?string $detalle = null, ?int $idUsuario = null): void
    {
        $idUsuario ??= Auth::id();

        if (! $idUsuario) {
            return;
        }

        Auditoria::create([
            'id_usuario' => $idUsuario,
            'modulo' => $modulo,
            'accion' => $accion,
            'tabla_afectada' => $tabla,
            'id_registro_afectado' => $idRegistro,
            'detalle' => $detalle ? mb_substr($detalle, 0, 1000) : null,
            'fecha_hora' => now(),
        ]);
    }
}
