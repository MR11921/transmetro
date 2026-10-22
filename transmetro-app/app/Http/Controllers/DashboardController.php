<?php

namespace App\Http\Controllers;

use App\Models\Alerta;
use App\Models\Bus;
use App\Models\Dispositivo;
use App\Models\Estacion;
use App\Models\Incidente;
use App\Models\Linea;
use App\Models\MovimientoPasajero;
use App\Models\Recorrido;
use App\Models\Ticket;

/** RF-025: vista general integrada del servicio. */
class DashboardController extends Controller
{
    public function __invoke()
    {
        $hoy = today();

        return view('dashboard', [
            'kpi' => [
                'lineas' => Linea::where('estado', 'ACTIVA')->count(),
                'estaciones' => Estacion::where('estado', 'ACTIVA')->count(),
                'buses' => Bus::where('estado', 'ACTIVO')->count(),
                'recorridos' => Recorrido::where('estado', 'EN_CURSO')->count(),
                'alertas' => Alerta::where('estado', 'PENDIENTE')->count(),
                'incidentes' => Incidente::where('estado', '!=', 'CERRADO')->count(),
                'tickets_hoy' => Ticket::whereDate('fecha_emision', $hoy)->count(),
                'entradas_hoy' => MovimientoPasajero::whereDate('fecha_hora', $hoy)->where('tipo_movimiento', 'ENTRADA')->where('resultado', 'VALIDADO')->count(),
                'dispositivos_falla' => Dispositivo::whereIn('estado', ['DESCONECTADO', 'MANTENIMIENTO'])->count(),
            ],
            'enCurso' => Recorrido::with(['bus', 'linea', 'piloto', 'linea.estaciones'])->where('estado', 'EN_CURSO')->latest('fecha_inicio')->get(),
            'alertas' => Alerta::with(['bus', 'estacion'])->where('estado', 'PENDIENTE')->latest('fecha_hora')->limit(6)->get(),
            'lineas' => Linea::with('estaciones')->withCount('buses')->where('estado', 'ACTIVA')->orderBy('nombre')->get(),
        ]);
    }
}
