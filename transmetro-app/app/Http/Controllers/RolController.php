<?php

namespace App\Http\Controllers;

use App\Models\Rol;

/** RF-001 / RN-015: matriz de qué puede hacer cada rol en cada módulo. */
class RolController extends Controller
{
    public function index()
    {
        $roles = Rol::withCount('usuarios')->orderBy('id_rol')->get();
        $permisos = config('transmetro.permisos');

        return view('roles.index', [
            'roles' => $roles,
            'modulos' => config('transmetro.modulos'),
            'permite' => fn (string $permiso, string $rol) => in_array('*', $permisos[$permiso] ?? [], true)
                || in_array($rol, $permisos[$permiso] ?? [], true),
        ]);
    }
}
