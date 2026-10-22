<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Acceso;
use App\Models\Guardia;
use App\Services\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** RF-006 / RN-007: cada acceso debe contar con al menos un guardia. */
class GuardiaAccesoController extends Controller
{
    public function index()
    {
        return view('accesos.guardias', [
            'accesos' => Acceso::with(['estacion', 'guardias'])->orderBy('id_estacion')->get(),
            'guardias' => Guardia::where('estado', 'ACTIVO')->orderBy('apellido')->get(),
        ]);
    }

    public function asignar(Request $request, Acceso $acceso)
    {
        $datos = $request->validate(['id_guardia' => ['required', Rule::exists('guardias', 'id_guardia')]]);

        if ($acceso->guardias()->where('guardias.id_guardia', $datos['id_guardia'])->exists()) {
            throw new ReglaNegocioException('El guardia ya está asignado a este acceso.');
        }

        $acceso->guardias()->attach($datos['id_guardia'], ['fecha_inicio' => now()]);
        Auditor::registrar('Accesos', 'ASIGNAR_GUARDIA', 'acceso_guardia', $acceso->id_acceso, "Guardia {$datos['id_guardia']}");

        return back()->with('ok', 'Guardia asignado.');
    }

    public function retirar(Acceso $acceso, Guardia $guardia)
    {
        if ($acceso->estado === 'ACTIVO' && $acceso->guardias()->count() <= 1) {
            throw new ReglaNegocioException('RN-007: el acceso quedaría sin guardia. Asigne otro guardia antes de retirar este.');
        }

        DB::table('acceso_guardia')->where('id_acceso', $acceso->id_acceso)->where('id_guardia', $guardia->id_guardia)
            ->whereNull('fecha_fin')->update(['fecha_fin' => now()]);
        Auditor::registrar('Accesos', 'RETIRAR_GUARDIA', 'acceso_guardia', $acceso->id_acceso, $guardia->nombre_completo);

        return back()->with('ok', 'Guardia retirado del acceso.');
    }
}
