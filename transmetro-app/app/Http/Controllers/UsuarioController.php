<?php

namespace App\Http\Controllers;

use App\Models\Piloto;
use App\Models\Rol;
use App\Models\Usuario;
use App\Services\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        return $this->formulario(new Usuario(['estado' => 'ACTIVO']));
    }

    public function store(Request $request)
    {
        $datos = $this->validar($request);

        $usuario = DB::transaction(function () use ($datos) {
            $usuario = Usuario::create([...collect($datos)->except(['password', 'id_piloto'])->all(), 'password_hash' => Hash::make($datos['password'])]);
            $this->vincularPiloto($usuario, $datos['id_piloto'] ?? null);

            return $usuario;
        });

        Auditor::registrar('Usuarios', 'CREAR', 'usuarios', $usuario->id_usuario, "Usuario {$usuario->usuario} con rol {$usuario->rol->nombre}");

        return redirect()->route('usuarios.index')->with('ok', 'Usuario creado correctamente.');
    }

    public function edit(Usuario $usuario)
    {
        return $this->formulario($usuario);
    }

    public function update(Request $request, Usuario $usuario)
    {
        $datos = $this->validar($request, $usuario);

        if ($usuario->id_usuario === $request->user()->id_usuario && ($datos['estado'] !== 'ACTIVO' || (int) $datos['id_rol'] !== $usuario->id_rol)) {
            return back()->with('error', 'No puede desactivarse ni cambiar su propio rol.');
        }

        $cambios = collect($datos)->except(['password', 'id_piloto'])->all();
        if (filled($datos['password'] ?? null)) {
            $cambios['password_hash'] = Hash::make($datos['password']);
        }
        DB::transaction(function () use ($usuario, $cambios, $datos) {
            $usuario->update($cambios);
            $this->vincularPiloto($usuario->fresh('rol'), $datos['id_piloto'] ?? null);
        });

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
            // Solo aplica al rol PILOTO: 'nuevo' crea el registro del piloto; un número vincula uno existente libre
            'id_piloto' => ['nullable', function ($atributo, $valor, $falla) use ($id) {
                if ($valor !== 'nuevo' && ! $this->pilotosDisponibles($id)->contains('id_piloto', (int) $valor)) {
                    $falla('El piloto elegido no existe o ya tiene otro usuario.');
                }
            }],
        ], [], ['id_rol' => 'rol', 'password' => 'contraseña', 'id_piloto' => 'piloto']);
    }

    private function formulario(Usuario $usuario)
    {
        $roles = Rol::orderBy('id_rol')->get();

        return view('usuarios.form', [
            'usuario' => $usuario,
            'roles' => $roles,
            'rolPiloto' => $roles->firstWhere('nombre', 'PILOTO')?->id_rol,
            'pilotos' => $this->pilotosDisponibles($usuario->id_usuario),
            'pilotoActual' => $usuario->exists ? $usuario->piloto?->id_piloto : null,
        ]);
    }

    /** Pilotos sin usuario, más el que ya tiene este usuario. */
    private function pilotosDisponibles(?int $idUsuario)
    {
        return Piloto::where(fn ($q) => $q->whereNull('id_usuario')->when($idUsuario, fn ($q) => $q->orWhere('id_usuario', $idUsuario)))
            ->orderBy('apellido')->get();
    }

    /**
     * RN-015: el rol PILOTO necesita su registro de piloto para operar sus recorridos en el TCP.
     * Con otro rol, el usuario deja de estar vinculado a un piloto.
     */
    private function vincularPiloto(Usuario $usuario, ?string $seleccion): void
    {
        $actual = $usuario->piloto()->first();

        if (! $usuario->tieneRol('PILOTO')) {
            $actual?->update(['id_usuario' => null]);

            return;
        }

        if ($seleccion === null) {
            return;
        }

        if ($seleccion === 'nuevo') {
            $actual?->update(['id_usuario' => null]);
            $piloto = Piloto::create([
                'nombre' => $usuario->nombre, 'apellido' => $usuario->apellido, 'correo' => $usuario->correo, 'id_usuario' => $usuario->id_usuario,
            ]);
            Auditor::registrar('Pilotos', 'CREAR', 'pilotos', $piloto->id_piloto, "Creado desde el usuario {$usuario->usuario}");

            return;
        }

        if ($actual?->id_piloto !== (int) $seleccion) {
            $actual?->update(['id_usuario' => null]);
            Piloto::whereKey((int) $seleccion)->update(['id_usuario' => $usuario->id_usuario]);
            Auditor::registrar('Pilotos', 'VINCULAR_USUARIO', 'pilotos', (int) $seleccion, "Usuario {$usuario->usuario}");
        }
    }
}
