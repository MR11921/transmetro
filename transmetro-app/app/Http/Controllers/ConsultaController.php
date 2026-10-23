<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Linea;
use App\Models\Recorrido;
use Illuminate\Http\Request;

/** RF-025: consulta integrada de una línea con sus estaciones, accesos, buses, recorridos, ocupación y alertas. */
class ConsultaController extends Controller
{
    public function index(Request $request)
    {
        $lineas = Linea::orderBy('nombre')->get();
        $linea = $lineas->firstWhere('id_linea', (int) $request->query('linea')) ?? $lineas->first();

        if ($linea) {
            $linea->load(['municipalidad', 'estaciones.accesos.guardias', 'estaciones.dispositivos', 'buses']);
        }

        return view('consulta.index', [
            'lineas' => $lineas,
            'linea' => $linea,
            'enCurso' => $linea ? Recorrido::with(['bus', 'piloto', 'linea.estaciones'])->where('id_linea', $linea->id_linea)
                ->where('estado', 'EN_CURSO')->get() : collect(),
            'alertas' => $linea ? Alerta::with(['bus', 'estacion'])->where('estado', 'PENDIENTE')
                ->where(fn ($q) => $q->whereIn('id_estacion', $linea->estaciones->pluck('id_estacion'))
                    ->orWhereIn('id_bus', $linea->buses->pluck('id_bus')))
                ->latest('fecha_hora')->limit(10)->get() : collect(),
        ]);
    }
}
