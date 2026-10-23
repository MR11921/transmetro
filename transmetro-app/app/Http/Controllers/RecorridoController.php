<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Bus;
use App\Models\BusLinea;
use App\Models\Piloto;
use App\Models\Recorrido;
use App\Services\Auditor;
use App\Services\ControlOcupacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF-019 recorridos · RF-015/016 TCP y ocupación · RF-018 demanda y tiempos de espera. */
class RecorridoController extends Controller
{
    public function index(Request $request)
    {
        $recorridos = Recorrido::with(['bus', 'linea', 'piloto'])->visiblesPara($request->user())
            ->when($request->query('estado'), fn ($q, $e) => $q->where('estado', $e))
            ->latest('fecha_inicio')->paginate(20)->withQueryString();

        return view('recorridos.index', compact('recorridos'));
    }

    public function create()
    {
        $asignados = BusLinea::whereNull('fecha_fin')->pluck('id_linea', 'id_bus');

        return view('recorridos.form', [
            'buses' => Bus::where('estado', 'ACTIVO')->whereIn('id_bus', $asignados->keys())->orderBy('numero_interno')->get()
                ->each(fn ($b) => $b->setAttribute('linea_nombre', $b->lineaActual()?->nombre)),
            'pilotos' => Piloto::where('estado', 'ACTIVO')->orderBy('apellido')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_bus' => ['required', Rule::exists('buses', 'id_bus')],
            'id_piloto' => ['required', Rule::exists('pilotos', 'id_piloto')],
            'fecha_inicio' => ['required', 'date'],
        ], [], ['id_bus' => 'bus', 'id_piloto' => 'piloto']);

        $bus = Bus::findOrFail($datos['id_bus']);
        $linea = $bus->lineaActual() ?? throw new ReglaNegocioException('El bus no tiene línea asignada.');

        if ($linea->estaciones()->count() < 2) {
            throw new ReglaNegocioException('La línea debe tener al menos dos estaciones para programar un recorrido.');
        }

        $recorrido = Recorrido::create([...$datos, 'id_linea' => $linea->id_linea, 'estado' => 'PROGRAMADO']);
        Auditor::registrar('Recorridos', 'PROGRAMAR', 'recorridos', $recorrido->id_recorrido, "Bus {$bus->numero_interno} en {$linea->nombre}");

        return redirect()->route('recorridos.show', $recorrido)->with('ok', 'Recorrido programado.');
    }

    /** Panel del recorrido y Terminal de Control de Pasajeros (TCP). */
    public function show(Recorrido $recorrido)
    {
        $this->autorizarPiloto($recorrido);
        $recorrido->load(['bus', 'linea.estaciones', 'piloto', 'controles.estacion', 'alertas.estacion']);

        return view('recorridos.show', [
            'r' => $recorrido,
            'siguiente' => $recorrido->siguienteEstacion(),
            'ocupacion' => $recorrido->ocupacionActual(),
            'porcentaje' => $recorrido->porcentajeOcupacion(),
        ]);
    }

    public function iniciar(Recorrido $recorrido)
    {
        $this->autorizarPiloto($recorrido);
        if ($recorrido->estado !== 'PROGRAMADO') {
            throw new ReglaNegocioException('Solo los recorridos programados pueden iniciarse.');
        }
        if ($recorrido->bus->recorridoEnCurso()) {
            throw new ReglaNegocioException('El bus ya tiene otro recorrido en curso.');
        }
        if (Recorrido::where('id_piloto', $recorrido->id_piloto)->where('estado', 'EN_CURSO')->exists()) {
            throw new ReglaNegocioException('El piloto ya conduce otro recorrido en curso.');
        }

        $recorrido->update(['estado' => 'EN_CURSO', 'fecha_inicio' => now()]);
        Auditor::registrar('Recorridos', 'INICIAR', 'recorridos', $recorrido->id_recorrido);

        return back()->with('ok', 'Recorrido iniciado.');
    }

    public function registrarParada(Request $request, Recorrido $recorrido, ControlOcupacion $control)
    {
        $this->autorizarPiloto($recorrido);
        $datos = $request->validate([
            'pasajeros_abordan' => ['required', 'integer', 'min:0', 'max:1000'],
            'pasajeros_descienden' => ['required', 'integer', 'min:0', 'max:1000'],
            'en_espera' => ['nullable', 'integer', 'min:0', 'max:5000'],
        ], [], ['pasajeros_abordan' => 'abordan', 'pasajeros_descienden' => 'descienden', 'en_espera' => 'en espera']);

        $r = $control->registrarParada($recorrido, $datos['pasajeros_abordan'], $datos['pasajeros_descienden'], $datos['en_espera'] ?? 0);

        $avisos = collect($r['alertas'])->pluck('descripcion')->join(' ');

        return back()->with('ok', 'Parada registrada. Ocupación: '.$r['control']->ocupacion_calculada.'.')->with('aviso', $avisos ?: null);
    }

    public function finalizar(Recorrido $recorrido)
    {
        $this->autorizarPiloto($recorrido);
        if ($recorrido->estado !== 'EN_CURSO') {
            throw new ReglaNegocioException('Solo los recorridos en curso pueden finalizarse.');
        }
        $recorrido->update(['estado' => 'FINALIZADO', 'fecha_fin' => now()]);
        Auditor::registrar('Recorridos', 'FINALIZAR', 'recorridos', $recorrido->id_recorrido);

        return back()->with('ok', 'Recorrido finalizado.');
    }

    public function cancelar(Recorrido $recorrido)
    {
        if (! in_array($recorrido->estado, ['PROGRAMADO', 'EN_CURSO'])) {
            throw new ReglaNegocioException('El recorrido ya fue cerrado.');
        }

        $recorrido->update(['estado' => 'CANCELADO', 'fecha_fin' => now()]);
        Auditor::registrar('Recorridos', 'CANCELAR', 'recorridos', $recorrido->id_recorrido);

        return back()->with('ok', 'Recorrido cancelado.');
    }

    /** RN-015: el piloto solo opera en el TCP los recorridos que tiene asignados. */
    private function autorizarPiloto(Recorrido $recorrido): void
    {
        abort_unless($recorrido->esOperablePor(request()->user()), 403, 'Este recorrido está asignado a otro piloto.');
    }
}
