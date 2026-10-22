<?php

use App\Http\Controllers\AlertaController;
use App\Http\Controllers\AuditoriaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BusController;
use App\Http\Controllers\CatalogoController;
use App\Http\Controllers\ControlAccesoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuardiaAccesoController;
use App\Http\Controllers\IncidenteController;
use App\Http\Controllers\LineaController;
use App\Http\Controllers\OperadorController;
use App\Http\Controllers\RecorridoController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UsuarioController;
use Illuminate\Support\Facades\Route;

// RF-002 Autenticación
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::get('/', DashboardController::class)->name('dashboard');

    // RF-001 Usuarios y permisos
    Route::middleware('modulo:usuarios')->group(function () {
        Route::resource('usuarios', UsuarioController::class)->except(['show', 'destroy']);
        Route::patch('usuarios/{usuario}/estado', [UsuarioController::class, 'estado'])->name('usuarios.estado');
    });

    // Catálogos: municipalidades, estaciones, accesos, guardias, pilotos, parqueos, métodos de pago, pasajeros, dispositivos
    Route::prefix('catalogos/{catalogo}')->name('catalogos.')->controller(CatalogoController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}/editar', 'edit')->name('edit')->whereNumber('id');
        Route::put('/{id}', 'update')->name('update')->whereNumber('id');
    });

    // RF-004 / RF-020 / RF-025 Líneas, recorridos y consulta integral
    Route::middleware('modulo:lineas')->group(function () {
        Route::resource('lineas', LineaController::class)->except('destroy')->parameters(['lineas' => 'linea']);
        Route::post('lineas/{linea}/estaciones', [LineaController::class, 'agregarEstacion'])->name('lineas.estaciones.agregar');
        Route::patch('lineas/{linea}/estaciones/{estacion}', [LineaController::class, 'distancia'])->name('lineas.estaciones.distancia');
        Route::delete('lineas/{linea}/estaciones/{estacion}', [LineaController::class, 'quitarEstacion'])->name('lineas.estaciones.quitar');
    });

    // RF-006 Guardias por acceso
    Route::middleware('modulo:accesos')->group(function () {
        Route::get('seguridad', [GuardiaAccesoController::class, 'index'])->name('seguridad.index');
        Route::post('seguridad/{acceso}', [GuardiaAccesoController::class, 'asignar'])->name('seguridad.asignar');
        Route::delete('seguridad/{acceso}/{guardia}', [GuardiaAccesoController::class, 'retirar'])->name('seguridad.retirar');
    });

    // RF-007 / RF-008 Buses, líneas y parqueos
    Route::middleware('modulo:buses')->group(function () {
        Route::resource('buses', BusController::class)->except('destroy')->parameters(['buses' => 'bus']);
        Route::post('buses/{bus}/linea', [BusController::class, 'asignarLinea'])->name('buses.linea');
        Route::delete('buses/{bus}/linea', [BusController::class, 'liberarLinea'])->name('buses.linea.liberar');
        Route::post('buses/{bus}/parqueo', [BusController::class, 'cambiarParqueo'])->name('buses.parqueo');
    });

    // RF-010 Operadores de estación
    Route::middleware('modulo:operadores')->group(function () {
        Route::get('operadores', [OperadorController::class, 'index'])->name('operadores.index');
        Route::post('operadores', [OperadorController::class, 'store'])->name('operadores.store');
        Route::patch('operadores/{operador}', [OperadorController::class, 'desactivar'])->name('operadores.desactivar');
    });

    // RF-011 / RF-012 Tickets y Terminal de Autoservicio
    Route::middleware('modulo:tickets')->group(function () {
        Route::get('tickets', [TicketController::class, 'index'])->name('tickets.index');
        Route::patch('tickets/{ticket}/anular', [TicketController::class, 'anular'])->name('tickets.anular');
    });
    Route::middleware('modulo:tat')->group(function () {
        Route::get('tat', [TicketController::class, 'tat'])->name('tat');
        Route::post('tat', [TicketController::class, 'emitir'])->name('tat.emitir');
    });

    // RF-013 / RF-014 Control de acceso de pasajeros
    Route::middleware('modulo:control_acceso')->group(function () {
        Route::get('control-acceso', [ControlAccesoController::class, 'index'])->name('acceso.index');
        Route::post('control-acceso', [ControlAccesoController::class, 'validar'])->name('acceso.validar');
    });

    // RF-019 Recorridos · RF-015/016/018 TCP
    Route::middleware('modulo:recorridos')->group(function () {
        Route::get('recorridos/crear', [RecorridoController::class, 'create'])->name('recorridos.create');
        Route::post('recorridos', [RecorridoController::class, 'store'])->name('recorridos.store');
        Route::patch('recorridos/{recorrido}/cancelar', [RecorridoController::class, 'cancelar'])->name('recorridos.cancelar');
    });
    Route::middleware('modulo:tcp')->group(function () {
        Route::get('recorridos', [RecorridoController::class, 'index'])->name('recorridos.index');
        Route::get('recorridos/{recorrido}', [RecorridoController::class, 'show'])->name('recorridos.show');
        Route::patch('recorridos/{recorrido}/iniciar', [RecorridoController::class, 'iniciar'])->name('recorridos.iniciar');
        Route::post('recorridos/{recorrido}/paradas', [RecorridoController::class, 'registrarParada'])->name('recorridos.parada');
        Route::patch('recorridos/{recorrido}/finalizar', [RecorridoController::class, 'finalizar'])->name('recorridos.finalizar');
    });

    // RF-017 Alertas
    Route::middleware('modulo:alertas')->group(function () {
        Route::get('alertas', [AlertaController::class, 'index'])->name('alertas.index');
        Route::patch('alertas/{alerta}', [AlertaController::class, 'cambiarEstado'])->name('alertas.estado');
    });

    // RF-022 Incidentes
    Route::middleware('modulo:incidentes')->group(function () {
        Route::get('incidentes', [IncidenteController::class, 'index'])->name('incidentes.index');
        Route::get('incidentes/crear', [IncidenteController::class, 'create'])->name('incidentes.create');
        Route::post('incidentes', [IncidenteController::class, 'store'])->name('incidentes.store');
        Route::patch('incidentes/{incidente}', [IncidenteController::class, 'cambiarEstado'])->name('incidentes.estado');
    });

    // RF-023 Reportes
    Route::middleware('modulo:reportes')->group(function () {
        Route::get('reportes', [ReporteController::class, 'index'])->name('reportes.index');
        Route::get('reportes/{tipo}', [ReporteController::class, 'show'])->name('reportes.show');
    });

    // RF-024 Auditoría
    Route::get('auditoria', [AuditoriaController::class, 'index'])->middleware('modulo:auditoria')->name('auditoria.index');
});
