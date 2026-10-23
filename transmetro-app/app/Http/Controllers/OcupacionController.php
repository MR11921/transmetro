<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Recorrido;
use Illuminate\Http\Request;

/** RF-015 TCP · RF-016 ocupación de buses · RF-018 demanda y tiempos de espera. */
class OcupacionController extends Controller
{
    public function index(Request $request)
    {
        $usuario = $request->user();

        return view('ocupacion.index', [
            'enCurso' => Recorrido::with(['bus', 'linea.estaciones', 'piloto'])->visiblesPara($usuario)->where('estado', 'EN_CURSO')->latest('fecha_inicio')->get(),
            'programados' => Recorrido::with(['bus', 'linea', 'piloto'])->visiblesPara($usuario)->where('estado', 'PROGRAMADO')->orderBy('fecha_inicio')->limit(8)->get(),
            'sinVinculo' => $usuario->tieneRol('PILOTO') && ! $usuario->piloto,
            'alertas' => Alerta::with(['bus', 'estacion'])->whereIn('tipo_alerta', ['ALTA_DEMANDA', 'BUS_LLENO', 'ESPERA_ADICIONAL'])
                ->latest('fecha_hora')->limit(8)->get(),
            'umbralAlta' => config('transmetro.umbral_alta_demanda'),
            'umbralBaja' => config('transmetro.umbral_baja_ocupacion'),
            'minutos' => config('transmetro.minutos_espera_adicional'),
        ]);
    }
}
