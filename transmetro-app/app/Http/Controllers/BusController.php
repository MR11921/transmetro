<?php

namespace App\Http\Controllers;

use App\Models\Bus;
use App\Models\Linea;
use App\Models\Parqueo;
use App\Services\Auditor;
use App\Services\GestionFlota;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF-007 buses y asignación · RF-008 parqueos con historial. */
class BusController extends Controller
{
    public function __construct(private GestionFlota $flota) {}

    public function index(Request $request)
    {
        $buses = Bus::orderBy('numero_interno')->get();

        return view('buses.index', compact('buses'));
    }

    public function create()
    {
        return view('buses.form', ['bus' => new Bus(['estado' => 'ACTIVO']), 'parqueos' => Parqueo::with('estacion')->where('estado', 'DISPONIBLE')->orderBy('codigo')->get()]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request, null);
        $bus = $this->flota->registrarBus(collect($datos)->except('id_parqueo')->all(), (int) $datos['id_parqueo']);
        Auditor::registrar('Buses', 'CREAR', 'buses', $bus->id_bus, "Bus {$bus->numero_interno} ({$bus->placa})");

        return redirect()->route('buses.show', $bus)->with('ok', 'Bus registrado con su parqueo asignado.');
    }

    public function show(Bus $bus)
    {
        $bus->load(['asignacionesLinea.linea', 'historialParqueo.parqueo.estacion', 'dispositivos']);

        return view('buses.show', [
            'bus' => $bus,
            'lineaActual' => $bus->lineaActual(),
            'parqueoActual' => $bus->parqueoActual(),
            'lineas' => Linea::where('estado', 'ACTIVA')->withCount(['buses', 'estaciones'])->orderBy('nombre')->get(),
            'parqueos' => Parqueo::with('estacion')->where('estado', 'DISPONIBLE')->orderBy('codigo')->get(),
            'recorridos' => $bus->recorridos()->with(['linea', 'piloto'])->latest('fecha_inicio')->limit(10)->get(),
        ]);
    }

    public function edit(Bus $bus)
    {
        return view('buses.form', ['bus' => $bus, 'parqueos' => collect()]);
    }

    public function update(Request $request, Bus $bus)
    {
        $bus->update($this->validar($request, $bus));
        Auditor::registrar('Buses', 'MODIFICAR', 'buses', $bus->id_bus, json_encode($bus->getChanges(), JSON_UNESCAPED_UNICODE));

        return redirect()->route('buses.show', $bus)->with('ok', 'Bus actualizado.');
    }

    public function asignarLinea(Request $request, Bus $bus)
    {
        $datos = $request->validate(['id_linea' => ['required', Rule::exists('lineas', 'id_linea')]]);
        $this->flota->asignarLinea($bus, Linea::findOrFail($datos['id_linea']));

        return back()->with('ok', 'Línea asignada correctamente.');
    }

    public function liberarLinea(Bus $bus)
    {
        $this->flota->liberarLinea($bus);

        return back()->with('ok', 'El bus quedó sin línea asignada.');
    }

    public function cambiarParqueo(Request $request, Bus $bus)
    {
        $datos = $request->validate(['id_parqueo' => ['required', Rule::exists('parqueos', 'id_parqueo')]]);
        $this->flota->cambiarParqueo($bus, (int) $datos['id_parqueo']);

        return back()->with('ok', 'Parqueo actualizado; el historial se conservó.');
    }

    private function validar(Request $request, ?Bus $bus): array
    {
        $id = $bus?->id_bus;

        return $request->validate([
            'placa' => ['required', 'max:20', Rule::unique('buses', 'placa')->ignore($id, 'id_bus')],
            'numero_interno' => ['required', 'max:30', Rule::unique('buses', 'numero_interno')->ignore($id, 'id_bus')],
            'capacidad_maxima' => ['required', 'integer', 'min:1', 'max:400'],
            'estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO', 'MANTENIMIENTO'])],
            // RN-006: un bus nuevo siempre nace con parqueo.
            'id_parqueo' => $bus ? ['prohibited'] : ['required', Rule::exists('parqueos', 'id_parqueo')],
        ], [], ['numero_interno' => 'número interno', 'capacidad_maxima' => 'capacidad', 'id_parqueo' => 'parqueo']);
    }
}
