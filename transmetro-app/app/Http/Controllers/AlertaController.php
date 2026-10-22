<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Alerta;
use App\Services\Auditor;
use Illuminate\Http\Request;

/** RF-017: gestión de alertas operativas. */
class AlertaController extends Controller
{
    public function index(Request $request)
    {
        $estado = $request->query('estado', 'PENDIENTE');

        $alertas = Alerta::with(['bus', 'estacion', 'recorrido'])
            ->when($estado !== 'TODAS', fn ($q) => $q->where('estado', $estado))
            ->when($request->query('tipo'), fn ($q, $t) => $q->where('tipo_alerta', $t))
            ->latest('fecha_hora')->paginate(20)->withQueryString();

        return view('alertas.index', [
            'alertas' => $alertas,
            'estado' => $estado,
            'tipos' => Alerta::distinct()->orderBy('tipo_alerta')->pluck('tipo_alerta'),
        ]);
    }

    public function cambiarEstado(Request $request, Alerta $alerta)
    {
        $nuevo = $request->validate(['estado' => ['required', 'in:ATENDIDA,CANCELADA']])['estado'];

        if ($alerta->estado !== 'PENDIENTE') {
            throw new ReglaNegocioException('La alerta ya fue gestionada.');
        }

        $alerta->update(['estado' => $nuevo]);
        Auditor::registrar('Alertas', $nuevo === 'ATENDIDA' ? 'ATENDER' : 'CANCELAR', 'alertas', $alerta->id_alerta, $alerta->tipo_alerta);

        return back()->with('ok', 'Alerta marcada como '.strtolower($nuevo).'.');
    }
}
