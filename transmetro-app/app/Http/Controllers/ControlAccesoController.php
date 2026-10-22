<?php

namespace App\Http\Controllers;

use App\Models\Acceso;
use App\Models\MovimientoPasajero;
use App\Services\ControlAcceso;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF-013 control de acceso · RF-014 registro de movimientos · RN-013. */
class ControlAccesoController extends Controller
{
    public function index(Request $request)
    {
        return view('acceso.index', [
            'accesos' => Acceso::with('estacion')->where('estado', 'ACTIVO')->orderBy('id_estacion')->get(),
            'movimientos' => MovimientoPasajero::with(['estacion', 'acceso', 'ticket'])
                ->when($request->query('resultado'), fn ($q, $r) => $q->where('resultado', $r))
                ->latest('fecha_hora')->paginate(20)->withQueryString(),
        ]);
    }

    public function validar(Request $request, ControlAcceso $control)
    {
        $datos = $request->validate([
            'codigo_ticket' => ['required', 'string', 'max:100'],
            'id_acceso' => ['required', Rule::exists('accesos', 'id_acceso')],
            'tipo_movimiento' => ['required', Rule::in(['ENTRADA', 'SALIDA'])],
        ], [], ['codigo_ticket' => 'código del ticket', 'id_acceso' => 'acceso']);

        $r = $control->validar($datos['codigo_ticket'], (int) $datos['id_acceso'], $datos['tipo_movimiento']);

        return redirect()->route('acceso.index')
            ->withInput($request->only('id_acceso', 'tipo_movimiento'))
            ->with($r['autorizado'] ? 'ok' : 'error', ($r['autorizado'] ? 'AUTORIZADO — ' : 'RECHAZADO — ').$r['mensaje']);
    }
}
