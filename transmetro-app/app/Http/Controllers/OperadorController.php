<?php

namespace App\Http\Controllers;

use App\Exceptions\ReglaNegocioException;
use App\Models\Estacion;
use App\Models\OperadorEstacion;
use App\Models\Usuario;
use App\Services\Auditor;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** RF-010: operadores asignados a estaciones. */
class OperadorController extends Controller
{
    public function index()
    {
        return view('operadores.index', [
            'asignaciones' => OperadorEstacion::with(['usuario', 'estacion'])->orderByDesc('estado')->orderByDesc('fecha_asignacion')->get(),
            'usuarios' => Usuario::whereHas('rol', fn ($q) => $q->where('nombre', 'OPERADOR_ESTACION'))->where('estado', 'ACTIVO')->orderBy('apellido')->get(),
            'estaciones' => Estacion::where('estado', 'ACTIVA')->orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $datos = $request->validate([
            'id_usuario' => ['required', Rule::exists('usuarios', 'id_usuario')],
            'id_estacion' => ['required', Rule::exists('estaciones', 'id_estacion')],
        ]);

        if (! Usuario::find($datos['id_usuario'])->tieneRol('OPERADOR_ESTACION')) {
            throw new ReglaNegocioException('Solo los usuarios con rol OPERADOR_ESTACION pueden asignarse a una estación.');
        }

        $existe = OperadorEstacion::where($datos)->where('estado', 'ACTIVO')->exists();
        if ($existe) {
            throw new ReglaNegocioException('El operador ya está asignado a esa estación.');
        }

        $op = OperadorEstacion::create([...$datos, 'fecha_asignacion' => today(), 'estado' => 'ACTIVO']);
        Auditor::registrar('Operadores', 'ASIGNAR', 'operadores_estacion', $op->id_operador);

        return back()->with('ok', 'Operador asignado a la estación.');
    }

    public function desactivar(OperadorEstacion $operador)
    {
        $operador->update(['estado' => 'INACTIVO']);
        Auditor::registrar('Operadores', 'DESACTIVAR', 'operadores_estacion', $operador->id_operador);

        return back()->with('ok', 'Asignación desactivada.');
    }
}
