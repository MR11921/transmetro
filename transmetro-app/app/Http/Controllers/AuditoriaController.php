<?php

namespace App\Http\Controllers;

use App\Models\Auditoria;
use App\Models\Usuario;
use Illuminate\Http\Request;

/** RF-024 / RNF-012: consulta de la bitácora de auditoría. */
class AuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $registros = Auditoria::with('usuario')
            ->when($request->query('id_usuario'), fn ($q, $u) => $q->where('id_usuario', $u))
            ->when($request->query('modulo'), fn ($q, $m) => $q->where('modulo', $m))
            ->when($request->query('desde'), fn ($q, $d) => $q->whereDate('fecha_hora', '>=', $d))
            ->when($request->query('hasta'), fn ($q, $h) => $q->whereDate('fecha_hora', '<=', $h))
            ->latest('fecha_hora')->orderByDesc('id_auditoria')->paginate(25)->withQueryString();

        return view('auditoria.index', [
            'registros' => $registros,
            'usuarios' => Usuario::orderBy('usuario')->get(),
            'modulos' => Auditoria::distinct()->orderBy('modulo')->pluck('modulo'),
        ]);
    }
}
