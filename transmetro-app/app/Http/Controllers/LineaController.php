<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Alerta;
use App\Models\Estacion;
use App\Models\Linea;
use App\Models\Municipalidad;
use App\Services\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/** RF-004 líneas y recorridos · RF-020 distancias · RF-025 consulta integral. */
class LineaController extends Controller
{
    public function index()
    {
        $lineas = Linea::with(['municipalidad', 'estaciones'])->withCount('buses')->orderBy('nombre')->get();

        return view('lineas.index', compact('lineas'));
    }

    public function create()
    {
        return view('lineas.form', ['linea' => new Linea(['estado' => 'ACTIVA']), 'municipalidades' => Municipalidad::orderBy('nombre')->get()]);
    }

    public function store(Request $request)
    {
        $linea = Linea::create($this->validar($request));
        Auditor::registrar('Líneas', 'CREAR', 'lineas', $linea->id_linea, $linea->nombre);

        return redirect()->route('lineas.show', $linea)->with('ok', 'Línea creada. Agregue ahora sus estaciones en orden.');
    }

    public function edit(Linea $linea)
    {
        return view('lineas.form', ['linea' => $linea, 'municipalidades' => Municipalidad::orderBy('nombre')->get()]);
    }

    public function update(Request $request, Linea $linea)
    {
        $linea->update($this->validar($request, $linea));
        Auditor::registrar('Líneas', 'MODIFICAR', 'lineas', $linea->id_linea, json_encode($linea->getChanges(), JSON_UNESCAPED_UNICODE));

        return redirect()->route('lineas.show', $linea)->with('ok', 'Línea actualizada.');
    }

    /** Consulta integral de la línea: estaciones, accesos, buses, recorridos y alertas. */
    public function show(Linea $linea)
    {
        $linea->load(['municipalidad', 'estaciones.accesos.guardias', 'estaciones.parqueos', 'buses']);
        $idsEstacion = $linea->estaciones->pluck('id_estacion');

        return view('lineas.show', [
            'linea' => $linea,
            'disponibles' => Estacion::whereNotIn('id_estacion', $idsEstacion)->where('estado', '!=', 'INACTIVA')->orderBy('nombre')->get(),
            'recorridos' => $linea->recorridos()->with(['bus', 'piloto'])->latest('fecha_inicio')->limit(8)->get(),
            'alertas' => Alerta::with('estacion')->where('estado', 'PENDIENTE')
                ->where(fn ($q) => $q->whereIn('id_estacion', $idsEstacion)->orWhereIn('id_bus', $linea->buses->pluck('id_bus')))
                ->latest('fecha_hora')->limit(8)->get(),
        ]);
    }

    /** RN-001: agrega una estación al final del recorrido. */
    public function agregarEstacion(Request $request, Linea $linea)
    {
        $datos = $request->validate([
            'id_estacion' => ['required', Rule::exists('estaciones', 'id_estacion')],
            'distancia_desde_anterior' => ['nullable', 'numeric', 'min:0', 'max:1000'],
        ], [], ['distancia_desde_anterior' => 'distancia']);

        if ($linea->estaciones()->where('estaciones.id_estacion', $datos['id_estacion'])->exists()) {
            throw new ReglaNegocioException('La estación ya forma parte de esta línea.');
        }

        $orden = (int) $linea->estaciones()->max('orden_estacion') + 1;
        $distancia = $orden === 1 ? null : ($datos['distancia_desde_anterior'] ?? null);

        if ($orden > 1 && $distancia === null) {
            throw new ReglaNegocioException('Indique la distancia desde la estación anterior.');
        }

        $linea->estaciones()->attach($datos['id_estacion'], ['orden_estacion' => $orden, 'distancia_desde_anterior' => $distancia]);
        Auditor::registrar('Líneas', 'AGREGAR_ESTACION', 'linea_estacion', $linea->id_linea, "Estación {$datos['id_estacion']} en orden {$orden}");

        return back()->with('ok', 'Estación agregada al recorrido.');
    }

    public function distancia(Request $request, Linea $linea, Estacion $estacion)
    {
        $datos = $request->validate(['distancia_desde_anterior' => ['required', 'numeric', 'min:0', 'max:1000']]);
        $linea->estaciones()->updateExistingPivot($estacion->id_estacion, $datos);
        Auditor::registrar('Líneas', 'MODIFICAR_DISTANCIA', 'linea_estacion', $linea->id_linea, "{$estacion->nombre}: {$datos['distancia_desde_anterior']} km");

        return back()->with('ok', 'Distancia actualizada.');
    }

    /** Quita una estación y reordena las siguientes conservando la secuencia (RN-001). */
    public function quitarEstacion(Linea $linea, Estacion $estacion)
    {
        if ($estacion->lineas()->count() === 1) {
            throw new ReglaNegocioException('La estación debe pertenecer al menos a una línea. Asígnela a otra línea antes de quitarla.');
        }

        if ($linea->recorridos()->where('estado', 'EN_CURSO')->exists()) {
            throw new ReglaNegocioException('La línea tiene recorridos en curso; no se puede modificar su secuencia.');
        }

        DB::transaction(function () use ($linea, $estacion) {
            $linea->estaciones()->detach($estacion->id_estacion);

            foreach ($linea->estaciones()->get()->values() as $i => $e) {
                DB::table('linea_estacion')->where('id_linea', $linea->id_linea)->where('id_estacion', $e->id_estacion)
                    ->update(['orden_estacion' => 1000 + $i]);
            }
            DB::table('linea_estacion')->where('id_linea', $linea->id_linea)->update(['orden_estacion' => DB::raw('orden_estacion - 999')]);
            DB::table('linea_estacion')->where('id_linea', $linea->id_linea)->where('orden_estacion', 1)->update(['distancia_desde_anterior' => null]);
        });

        Auditor::registrar('Líneas', 'QUITAR_ESTACION', 'linea_estacion', $linea->id_linea, $estacion->nombre);

        return back()->with('ok', 'Estación retirada del recorrido.');
    }

    private function validar(Request $request, ?Linea $linea = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'max:100', Rule::unique('lineas', 'nombre')->ignore($linea?->id_linea, 'id_linea')],
            'id_municipalidad' => ['required', Rule::exists('municipalidades', 'id_municipalidad')],
            'descripcion' => ['nullable', 'max:250'],
            'estado' => ['required', Rule::in(['ACTIVA', 'INACTIVA'])],
        ], [], ['id_municipalidad' => 'municipalidad']);
    }
}
