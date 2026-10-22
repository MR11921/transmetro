<?php

namespace App\Http\Controllers;

use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/** RF-001: gestión de usuarios, roles y estado. */
class UsuarioController extends Controller
{
    public function index()
    {
        return view('usuarios.index', ['usuarios' => Usuario::with('rol')->orderBy('apellido')->get()]);
    }

    public function create()
    {
        return view('usuarios.form', ['usuario' => new Usuario(['estado' => 'ACTIVO']), 'roles' => Rol::orderBy('id_rol')->get()]);
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);
        $usuario = Usuario::create([...collect($datos)->except('password')->all(), 'password_hash' => Hash::make($datos['password'])]);

        Auditor::registrar('Usuarios', 'CREAR', 'usuarios', $usuario->id_usuario, "Usuario {$usuario->usuario} con rol {$usuario->rol->nombre}");

        return redirect()->route('usuarios.index')->with('ok', 'Usuario creado correctamente.');
    }

    public function edit(Usuario $usuario)
    {
        return view('usuarios.form', ['usuario' => $usuario, 'roles' => Rol::orderBy('id_rol')->get()]);
    }

    public function update(Request $request, Usuario $usuario)
    {
        $datos = $this->validar($request, $usuario);

        if ($usuario->id_usuario === $request->user()->id_usuario && ($datos['estado'] !== 'ACTIVO' || (int) $datos['id_rol'] !== $usuario->id_rol)) {
            return back()->with('error', 'No puede desactivarse ni cambiar su propio rol.');
        }

        $cambios = collect($datos)->except('password')->all();
        if (filled($datos['password'] ?? null)) {
            $cambios['password_hash'] = Hash::make($datos['password']);
        }
        $usuario->update($cambios);

        Auditor::registrar('Usuarios', 'MODIFICAR', 'usuarios', $usuario->id_usuario, json_encode(collect($usuario->getChanges())->except('password_hash')->keys()));

        return redirect()->route('usuarios.index')->with('ok', 'Usuario actualizado.');
    }

    public function estado(Request $request, Usuario $usuario)
    {
        if ($usuario->id_usuario === $request->user()->id_usuario) {
            return back()->with('error', 'No puede desactivar su propio usuario.');
        }

        $usuario->update(['estado' => $usuario->estado === 'ACTIVO' ? 'INACTIVO' : 'ACTIVO']);
        Auditor::registrar('Usuarios', $usuario->estado === 'ACTIVO' ? 'ACTIVAR' : 'DESACTIVAR', 'usuarios', $usuario->id_usuario);

        return back()->with('ok', "Usuario {$usuario->usuario} ahora está {$usuario->estado}.");
    }

    private function validar(Request $request, ?Usuario $usuario = null): array
    {
        $id = $usuario?->id_usuario;

        return $request->validate([
            'id_rol' => ['required', Rule::exists('roles', 'id_rol')],
            'nombre' => ['required', 'max:100'],
            'apellido' => ['required', 'max:100'],
            'usuario' => ['required', 'max:50', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('usuarios', 'usuario')->ignore($id, 'id_usuario')],
            'correo' => ['required', 'email', 'max:150', Rule::unique('usuarios', 'correo')->ignore($id, 'id_usuario')],
            'estado' => ['required', Rule::in(['ACTIVO', 'INACTIVO'])],
            'password' => [$usuario ? 'nullable' : 'required', 'confirmed', Password::min(8)->letters()->numbers()],
        ], [], ['id_rol' => 'rol', 'password' => 'contraseña']);
    }
}
