<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Esquema del Sistema de Gestión y Control de Transmetro.
 * Replica las tablas, llaves y restricciones del script Oracle
 * (database/oracle/transmetro_oracle.sql) para poder trabajar en
 * desarrollo con SQLite y en producción con Oracle.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Seguridad y usuarios
        Schema::create('roles', function (Blueprint $t) {
            $t->id('id_rol');
            $t->string('nombre', 50)->unique();
            $t->string('descripcion', 200)->nullable();
        });

        Schema::create('usuarios', function (Blueprint $t) {
            $t->id('id_usuario');
            $t->foreignId('id_rol')->constrained('roles', 'id_rol');
            $t->string('nombre', 100);
            $t->string('apellido', 100);
            $t->string('usuario', 50)->unique();
            $t->string('correo', 150)->unique();
            $t->string('password_hash', 255);
            $t->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
            $t->timestamp('fecha_creacion')->useCurrent();
        });

        // 2. Municipalidades
        Schema::create('municipalidades', function (Blueprint $t) {
            $t->id('id_municipalidad');
            $t->string('nombre', 100)->unique();
            $t->string('departamento', 100)->nullable();
            $t->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
        });

        // 3. Líneas
        Schema::create('lineas', function (Blueprint $t) {
            $t->id('id_linea');
            $t->foreignId('id_municipalidad')->constrained('municipalidades', 'id_municipalidad');
            $t->string('nombre', 100)->unique();
            $t->string('descripcion', 250)->nullable();
            $t->enum('estado', ['ACTIVA', 'INACTIVA'])->default('ACTIVA');
        });

        // 4. Estaciones
        Schema::create('estaciones', function (Blueprint $t) {
            $t->id('id_estacion');
            $t->foreignId('id_municipalidad')->constrained('municipalidades', 'id_municipalidad');
            $t->string('nombre', 100)->unique();
            $t->string('direccion', 250)->nullable();
            $t->decimal('latitud', 10, 7)->nullable();
            $t->decimal('longitud', 10, 7)->nullable();
            $t->enum('tiene_parqueo', ['S', 'N'])->default('N');
            $t->enum('estado', ['ACTIVA', 'INACTIVA', 'MANTENIMIENTO'])->default('ACTIVA');
        });

        // 5. Relación líneas–estaciones (RN-001, RN-002, RN-009)
        Schema::create('linea_estacion', function (Blueprint $t) {
            $t->foreignId('id_linea')->constrained('lineas', 'id_linea');
            $t->foreignId('id_estacion')->constrained('estaciones', 'id_estacion');
            $t->unsignedInteger('orden_estacion');
            $t->decimal('distancia_desde_anterior', 10, 2)->nullable();
            $t->primary(['id_linea', 'id_estacion']);
            // El UNIQUE ya crea el índice (id_linea, orden_estacion); Oracle rechaza un segundo índice igual (ORA-01408).
            $t->unique(['id_linea', 'orden_estacion']);
        });

        // 6. Accesos y guardias (RN-003, RN-007)
        Schema::create('accesos', function (Blueprint $t) {
            $t->id('id_acceso');
            $t->foreignId('id_estacion')->constrained('estaciones', 'id_estacion');
            $t->string('nombre', 100);
            $t->string('tipo', 30)->nullable();
            $t->enum('estado', ['ACTIVO', 'INACTIVO', 'MANTENIMIENTO'])->default('ACTIVO');
        });

        Schema::create('guardias', function (Blueprint $t) {
            $t->id('id_guardia');
            $t->string('nombre', 100);
            $t->string('apellido', 100);
            $t->string('telefono', 30)->nullable();
            $t->string('correo', 150)->nullable();
            $t->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
        });

        Schema::create('acceso_guardia', function (Blueprint $t) {
            $t->foreignId('id_acceso')->constrained('accesos', 'id_acceso');
            $t->foreignId('id_guardia')->constrained('guardias', 'id_guardia');
            $t->dateTime('fecha_inicio')->useCurrent();
            $t->dateTime('fecha_fin')->nullable();
            $t->primary(['id_acceso', 'id_guardia', 'fecha_inicio']);
        });

        // 7. Pilotos y operadores
        Schema::create('pilotos', function (Blueprint $t) {
            $t->id('id_piloto');
            $t->string('nombre', 100);
            $t->string('apellido', 100);
            $t->string('telefono', 30)->nullable();
            $t->string('correo', 150)->nullable();
            $t->string('direccion', 250)->nullable();
            $t->string('formacion_academica', 250)->nullable();
            $t->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
        });

        Schema::create('operadores_estacion', function (Blueprint $t) {
            $t->id('id_operador');
            $t->foreignId('id_usuario')->constrained('usuarios', 'id_usuario');
            $t->foreignId('id_estacion')->constrained('estaciones', 'id_estacion');
            $t->date('fecha_asignacion')->useCurrent();
            $t->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
        });

        // 8. Parqueos
        Schema::create('parqueos', function (Blueprint $t) {
            $t->id('id_parqueo');
            $t->foreignId('id_estacion')->constrained('estaciones', 'id_estacion');
            $t->string('codigo', 50)->unique();
            $t->string('ubicacion', 250)->nullable();
            $t->enum('estado', ['DISPONIBLE', 'OCUPADO', 'MANTENIMIENTO'])->default('DISPONIBLE');
        });

        // 9. Buses
        Schema::create('buses', function (Blueprint $t) {
            $t->id('id_bus');
            $t->string('placa', 20)->unique();
            $t->string('numero_interno', 30)->unique();
            $t->unsignedInteger('capacidad_maxima');
            $t->enum('estado', ['ACTIVO', 'INACTIVO', 'MANTENIMIENTO'])->default('ACTIVO');
        });

        // 10. Asignación de buses a líneas (RN-004, RN-005)
        Schema::create('bus_linea', function (Blueprint $t) {
            $t->foreignId('id_bus')->constrained('buses', 'id_bus');
            $t->foreignId('id_linea')->constrained('lineas', 'id_linea');
            $t->dateTime('fecha_inicio')->useCurrent();
            $t->dateTime('fecha_fin')->nullable();
            $t->primary(['id_bus', 'fecha_inicio']);
        });

        // 11. Historial de parqueo de buses (RN-006)
        Schema::create('bus_parqueo', function (Blueprint $t) {
            $t->id('id_bus_parqueo');
            $t->foreignId('id_bus')->constrained('buses', 'id_bus');
            $t->foreignId('id_parqueo')->constrained('parqueos', 'id_parqueo');
            $t->dateTime('fecha_inicio')->useCurrent();
            $t->dateTime('fecha_fin')->nullable();
        });

        // 12. Tickets y métodos de pago
        Schema::create('metodos_pago', function (Blueprint $t) {
            $t->id('id_metodo_pago');
            $t->string('nombre', 50)->unique();
            $t->string('descripcion', 200)->nullable();
            $t->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
        });

        Schema::create('pasajeros', function (Blueprint $t) {
            $t->id('id_pasajero');
            $t->string('nombre', 100)->nullable();
            $t->string('apellido', 100)->nullable();
            $t->string('identificador', 100)->nullable()->unique();
            $t->string('tipo_pasajero', 30)->nullable();
            $t->enum('estado', ['ACTIVO', 'INACTIVO'])->default('ACTIVO');
        });

        Schema::create('tickets', function (Blueprint $t) {
            $t->id('id_ticket');
            $t->foreignId('id_pasajero')->nullable()->constrained('pasajeros', 'id_pasajero');
            $t->foreignId('id_metodo_pago')->constrained('metodos_pago', 'id_metodo_pago');
            $t->string('codigo_ticket', 100)->unique();
            $t->timestamp('fecha_emision')->useCurrent();
            $t->decimal('valor', 10, 2);
            $t->enum('estado', ['VALIDO', 'UTILIZADO', 'ANULADO', 'VENCIDO'])->default('VALIDO');
        });

        // 13. Dispositivos (TAT, TCP, control de acceso…)
        Schema::create('dispositivos', function (Blueprint $t) {
            $t->id('id_dispositivo');
            $t->foreignId('id_estacion')->nullable()->constrained('estaciones', 'id_estacion');
            $t->foreignId('id_bus')->nullable()->constrained('buses', 'id_bus');
            $t->string('codigo_inventario', 100)->unique();
            $t->enum('tipo', ['TAT', 'TCP', 'CONTROL_ACCESO', 'LECTOR', 'OTRO']);
            $t->enum('estado', ['ACTIVO', 'INACTIVO', 'MANTENIMIENTO', 'DESCONECTADO'])->default('ACTIVO');
            $t->date('fecha_instalacion')->nullable();
        });

        // 14. Movimientos de pasajeros (RF-013, RF-014)
        Schema::create('movimientos_pasajeros', function (Blueprint $t) {
            $t->id('id_movimiento');
            $t->foreignId('id_pasajero')->nullable()->constrained('pasajeros', 'id_pasajero');
            $t->foreignId('id_ticket')->nullable()->constrained('tickets', 'id_ticket');
            $t->foreignId('id_estacion')->constrained('estaciones', 'id_estacion');
            $t->foreignId('id_acceso')->nullable()->constrained('accesos', 'id_acceso');
            $t->enum('tipo_movimiento', ['ENTRADA', 'SALIDA']);
            $t->timestamp('fecha_hora')->useCurrent()->index('idx_mov_fecha');
            $t->enum('resultado', ['VALIDADO', 'RECHAZADO'])->default('VALIDADO');
        });

        // 15. Recorridos (RF-019)
        Schema::create('recorridos', function (Blueprint $t) {
            $t->id('id_recorrido');
            $t->foreignId('id_bus')->constrained('buses', 'id_bus');
            $t->foreignId('id_linea')->constrained('lineas', 'id_linea');
            $t->foreignId('id_piloto')->constrained('pilotos', 'id_piloto');
            $t->timestamp('fecha_inicio')->index('idx_rec_fecha');
            $t->timestamp('fecha_fin')->nullable();
            $t->enum('estado', ['PROGRAMADO', 'EN_CURSO', 'FINALIZADO', 'CANCELADO'])->default('PROGRAMADO');
        });

        // 16. Control de pasajeros en buses (RF-015, RF-016)
        Schema::create('control_pasajeros', function (Blueprint $t) {
            $t->id('id_control');
            $t->foreignId('id_recorrido')->constrained('recorridos', 'id_recorrido');
            $t->foreignId('id_estacion')->constrained('estaciones', 'id_estacion');
            $t->unsignedInteger('pasajeros_abordan')->default(0);
            $t->unsignedInteger('pasajeros_descienden')->default(0);
            $t->unsignedInteger('ocupacion_calculada')->default(0);
            $t->timestamp('fecha_hora')->useCurrent();
            $t->index('id_recorrido', 'idx_control_recorrido');
        });

        // 17. Alertas (RF-017, RF-018)
        Schema::create('alertas', function (Blueprint $t) {
            $t->id('id_alerta');
            $t->foreignId('id_bus')->nullable()->constrained('buses', 'id_bus');
            $t->foreignId('id_estacion')->nullable()->constrained('estaciones', 'id_estacion');
            $t->foreignId('id_recorrido')->nullable()->constrained('recorridos', 'id_recorrido');
            $t->string('tipo_alerta', 50);
            $t->string('descripcion', 500);
            $t->enum('nivel', ['BAJA', 'MEDIA', 'ALTA', 'CRITICA'])->default('MEDIA');
            $t->timestamp('fecha_hora')->useCurrent();
            $t->enum('estado', ['PENDIENTE', 'ATENDIDA', 'CANCELADA'])->default('PENDIENTE')->index('idx_alertas_estado');
        });

        // 18. Incidentes (RF-022)
        Schema::create('incidentes', function (Blueprint $t) {
            $t->id('id_incidente');
            $t->foreignId('id_bus')->nullable()->constrained('buses', 'id_bus');
            $t->foreignId('id_estacion')->nullable()->constrained('estaciones', 'id_estacion');
            $t->foreignId('id_dispositivo')->nullable()->constrained('dispositivos', 'id_dispositivo');
            $t->foreignId('id_usuario_registro')->constrained('usuarios', 'id_usuario');
            $t->string('tipo_incidente', 50);
            $t->string('descripcion', 500);
            $t->timestamp('fecha_hora')->useCurrent();
            $t->enum('estado', ['ABIERTO', 'EN_PROCESO', 'CERRADO'])->default('ABIERTO')->index('idx_incidentes_estado');
        });

        // 19. Auditoría (RF-024)
        Schema::create('auditoria', function (Blueprint $t) {
            $t->id('id_auditoria');
            $t->foreignId('id_usuario')->constrained('usuarios', 'id_usuario');
            $t->string('modulo', 100);
            $t->string('accion', 100);
            $t->string('tabla_afectada', 100)->nullable();
            $t->unsignedBigInteger('id_registro_afectado')->nullable();
            $t->timestamp('fecha_hora')->useCurrent()->index('idx_auditoria_fecha');
            $t->string('detalle', 1000)->nullable();
        });
    }

    public function down(): void
    {
        foreach ([
            'auditoria', 'incidentes', 'alertas', 'control_pasajeros', 'recorridos',
            'movimientos_pasajeros', 'dispositivos', 'tickets', 'pasajeros', 'metodos_pago',
            'bus_parqueo', 'bus_linea', 'buses', 'parqueos', 'operadores_estacion', 'pilotos',
            'acceso_guardia', 'guardias', 'accesos', 'linea_estacion', 'estaciones', 'lineas',
            'municipalidades', 'usuarios', 'roles',
        ] as $tabla) {
            Schema::dropIfExists($tabla);
        }
    }
};
