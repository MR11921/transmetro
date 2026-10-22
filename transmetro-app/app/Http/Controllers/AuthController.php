<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use App\Services\Auditor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/** RF-002: inicio de sesión con credenciales y rol. */
class AuthController extends Controller
{
    public function create()
    {
        return view('auth.login');
    }

    public function store(Request $request)
    {
        $credenciales = $request->validate([
            'usuario' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $llave = 'login:'.mb_strtolower($credenciales['usuario']).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($llave, 5)) {
            throw ValidationException::withMessages(['usuario' => 'Demasiados intentos. Intente de nuevo en '.RateLimiter::availableIn($llave).' segundos.']);
        }

        if (! Auth::attempt([...$credenciales, 'estado' => 'ACTIVO'])) {
            RateLimiter::hit($llave, 60);

            if ($usuario = Usuario::where('usuario', $credenciales['usuario'])->first()) {
                Auditor::registrar('Autenticación', 'LOGIN_FALLIDO', 'usuarios', $usuario->id_usuario, 'IP '.$request->ip(), $usuario->id_usuario);
            }

            throw ValidationException::withMessages(['usuario' => 'Credenciales inválidas o usuario inactivo.']);
        }

        RateLimiter::clear($llave);
        $request->session()->regenerate();
        Auditor::registrar('Autenticación', 'LOGIN', 'usuarios', Auth::id(), 'IP '.$request->ip());

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request)
    {
        Auditor::registrar('Autenticación', 'LOGOUT', 'usuarios', Auth::id());
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
