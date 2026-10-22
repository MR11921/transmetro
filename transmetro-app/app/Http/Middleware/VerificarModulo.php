<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** RNF-001 / RN-015: restringe cada módulo a los roles autorizados. */
class VerificarModulo
{
    public function handle(Request $request, Closure $next, string $modulo): Response
    {
        $usuario = $request->user();

        abort_unless($usuario && $usuario->estado === 'ACTIVO' && $usuario->puede($modulo), 403, 'No tiene permisos para acceder a este módulo.');

        return $next($request);
    }
}
