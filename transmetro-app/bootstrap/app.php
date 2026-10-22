<?php

use App\Exceptions\ReglaNegocioException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['modulo' => \App\Http\Middleware\VerificarModulo::class]);
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/');
        // En la nube la app corre detrás de Caddy (HTTPS); confiar en sus cabeceras X-Forwarded-*.
        $middleware->trustProxies(at: env('TRUSTED_PROXIES', '127.0.0.1'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Una violación de regla de negocio regresa al formulario con el mensaje.
        $exceptions->render(fn (ReglaNegocioException $e, Request $request) => back()->withInput()->with('error', $e->getMessage()));
    })->create();
