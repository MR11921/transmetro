<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\Dispositivo;
use App\Models\Estacion;
use App\Models\Incidente;
use App\Services\Auditor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF-022: registro y seguimiento de incidentes. */
class IncidenteController extends Controller
{
    public const TIPOS = ['BUS', 'ESTACION', 'PASAJERO', 'DISPOSITIVO', 'COMUNICACION', 'SEGURIDAD'];

    public function index(Request $request)
    {
        $incidentes = Incidente::with(['bus', 'estacion', 'dispositivo', 'usuario'])
            ->when($request->query('estado'), fn ($q, $e) => $q->where('estado', $e))
            ->latest('fecha_hora')->paginate(20)->withQueryString();

        return view('incidentes.index', compact('incidentes'));
    }

    public function create()
    {
        return view('incidentes.form', [
            'tipos' => self::TIPOS,
            'buses' => Bus::orderBy('numero_interno')->get(),
            'estaciones' => Estacion::orderBy('nombre')->get(),
            'dispositivos' => Dispositivo::orderBy('codigo_inventario')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'tipo_incidente' => ['required', Rule::in(self::TIPOS)],
            'descripcion' => ['required', 'max:500'],
            'id_bus' => ['nullable', Rule::exists('buses', 'id_bus')],
            'id_estacion' => ['nullable', Rule::exists('estaciones', 'id_estacion')],
            'id_dispositivo' => ['nullable', Rule::exists('dispositivos', 'id_dispositivo')],
        ], [], ['tipo_incidente' => 'tipo', 'id_bus' => 'bus', 'id_estacion' => 'estación', 'id_dispositivo' => 'dispositivo']);

        $incidente = Incidente::create([...$datos, 'id_usuario_registro' => $request->user()->id_usuario, 'fecha_hora' => now(), 'estado' => 'ABIERTO']);
        Auditor::registrar('Incidentes', 'REGISTRAR', 'incidentes', $incidente->id_incidente, $incidente->tipo_incidente);

        return redirect()->route('incidentes.index')->with('ok', 'Incidente registrado.');
    }

    public function cambiarEstado(Request $request, Incidente $incidente)
    {
        $nuevo = $request->validate(['estado' => ['required', Rule::in(['EN_PROCESO', 'CERRADO'])]])['estado'];
        $anterior = $incidente->estado;
        $incidente->update(['estado' => $nuevo]);
        Auditor::registrar('Incidentes', 'CAMBIAR_ESTADO', 'incidentes', $incidente->id_incidente, "{$anterior} → {$nuevo}");

        return back()->with('ok', 'Incidente actualizado.');
    }
}
