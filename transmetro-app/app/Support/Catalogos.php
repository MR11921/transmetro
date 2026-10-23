<?php

namespace App\Support;

use App\Models;

/**
 * Definición de los catálogos administrados con el CRUD genérico.
 * Cada campo: etiqueta, tipo (text|email|number|date|enum|select), reglas
 * de validación y, para select, el modelo y la columna a mostrar.
 */
class Catalogos
{
    public static function todos(): array
    {
        return [
            'municipalidades' => [
                'titulo' => 'Municipalidades', 'singular' => 'municipalidad', 'rf' => 'RF-003',
                'modelo' => Models\Municipalidad::class, 'orden' => 'nombre',
                'campos' => [
                    'nombre' => ['Nombre', 'text', 'required|max:100', 'unico' => true],
                    'departamento' => ['Departamento', 'text', 'nullable|max:100'],
                    'estado' => ['Estado', 'enum', 'required', 'opciones' => ['ACTIVO', 'INACTIVO']],
                ],
            ],
            'estaciones' => [
                'titulo' => 'Estaciones', 'singular' => 'estación', 'rf' => 'RF-005',
                'modelo' => Models\Estacion::class, 'orden' => 'nombre', 'con' => ['municipalidad'],
                'campos' => [
                    'nombre' => ['Nombre', 'text', 'required|max:100', 'unico' => true],
                    'id_municipalidad' => ['Municipalidad', 'select', 'required', 'fuente' => [Models\Municipalidad::class, 'nombre'], 'mostrar' => 'municipalidad.nombre'],
                    'direccion' => ['Dirección', 'text', 'nullable|max:250'],
                    'latitud' => ['Latitud', 'number', 'nullable|numeric|between:-90,90', 'oculto' => true],
                    'longitud' => ['Longitud', 'number', 'nullable|numeric|between:-180,180', 'oculto' => true],
                    'tiene_parqueo' => ['¿Tiene parqueo?', 'enum', 'required', 'opciones' => ['N', 'S']],
                    'estado' => ['Estado', 'enum', 'required', 'opciones' => ['ACTIVA', 'INACTIVA', 'MANTENIMIENTO']],
                ],
            ],
            'accesos' => [
                'titulo' => 'Accesos', 'singular' => 'acceso', 'rf' => 'RF-006',
                'modelo' => Models\Acceso::class, 'orden' => 'id_estacion', 'con' => ['estacion', 'guardias'],
                'campos' => [
                    'id_estacion' => ['Estación', 'select', 'required', 'fuente' => [Models\Estacion::class, 'nombre'], 'mostrar' => 'estacion.nombre'],
                    'nombre' => ['Nombre', 'text', 'required|max:100'],
                    'tipo' => ['Tipo', 'enum', 'nullable', 'opciones' => ['TORNIQUETE', 'BARRERA', 'PUERTA', 'RAMPA']],
                    'estado' => ['Estado', 'enum', 'required', 'opciones' => ['ACTIVO', 'INACTIVO', 'MANTENIMIENTO']],
                ],
            ],
            'guardias' => [
                'titulo' => 'Guardias de seguridad', 'singular' => 'guardia', 'rf' => 'RF-006',
                'modelo' => Models\Guardia::class, 'orden' => 'apellido',
                'campos' => [
                    'nombre' => ['Nombre', 'text', 'required|max:100'],
                    'apellido' => ['Apellido', 'text', 'required|max:100'],
                    'telefono' => ['Teléfono', 'text', 'nullable|max:30'],
                    'correo' => ['Correo', 'email', 'nullable|email|max:150'],
                    'estado' => ['Estado', 'enum', 'required', 'opciones' => ['ACTIVO', 'INACTIVO']],
                ],
            ],
            'pilotos' => [
                'titulo' => 'Pilotos', 'singular' => 'piloto', 'rf' => 'RF-009',
                'modelo' => Models\Piloto::class, 'orden' => 'apellido', 'con' => ['usuario'],
                'campos' => [
                    'nombre' => ['Nombre', 'text', 'required|max:100'],
                    'apellido' => ['Apellido', 'text', 'required|max:100'],
                    'telefono' => ['Teléfono', 'text', 'nullable|max:30'],
                    'correo' => ['Correo', 'email', 'nullable|email|max:150'],
                    'direccion' => ['Dirección de residencia', 'text', 'nullable|max:250'],
                    'formacion_academica' => ['Formación académica', 'text', 'nullable|max:250'],
                    // Usuario con el que el piloto entra al sistema y opera sus recorridos en el TCP
                    'id_usuario' => ['Usuario del sistema', 'select', 'nullable', 'unico' => true, 'mostrar' => 'usuario.usuario',
                        'fuente' => [Models\Usuario::class, 'usuario'], 'donde' => fn ($q) => $q->whereHas('rol', fn ($r) => $r->where('nombre', 'PILOTO'))],
                    'estado' => ['Estado', 'enum', 'required', 'opciones' => ['ACTIVO', 'INACTIVO']],
                ],
            ],
            'parqueos' => [
                'titulo' => 'Parqueos', 'singular' => 'parqueo', 'rf' => 'RF-008',
                'modelo' => Models\Parqueo::class, 'orden' => 'codigo', 'con' => ['estacion'],
                'campos' => [
                    'codigo' => ['Código', 'text', 'required|max:50', 'unico' => true],
                    'id_estacion' => ['Estación', 'select', 'required', 'fuente' => [Models\Estacion::class, 'nombre'], 'mostrar' => 'estacion.nombre'],
                    'ubicacion' => ['Ubicación', 'text', 'nullable|max:250'],
                    'estado' => ['Estado', 'enum', 'required', 'opciones' => ['DISPONIBLE', 'OCUPADO', 'MANTENIMIENTO']],
                ],
            ],
            'metodos_pago' => [
                'titulo' => 'Métodos de pago', 'singular' => 'método de pago', 'rf' => 'RF-011',
                'modelo' => Models\MetodoPago::class, 'orden' => 'nombre',
                'campos' => [
                    'nombre' => ['Nombre', 'text', 'required|max:50', 'unico' => true],
                    'descripcion' => ['Descripción', 'text', 'nullable|max:200'],
                    'estado' => ['Estado', 'enum', 'required', 'opciones' => ['ACTIVO', 'INACTIVO']],
                ],
            ],
            'pasajeros' => [
                'titulo' => 'Pasajeros', 'singular' => 'pasajero', 'rf' => 'RF-011',
                'modelo' => Models\Pasajero::class, 'orden' => 'apellido',
                'campos' => [
                    'nombre' => ['Nombre', 'text', 'nullable|max:100'],
                    'apellido' => ['Apellido', 'text', 'nullable|max:100'],
                    'identificador' => ['Identificador / tarjeta', 'text', 'nullable|max:100', 'unico' => true],
                    'tipo_pasajero' => ['Tipo', 'enum', 'nullable', 'opciones' => ['GENERAL', 'ESTUDIANTE', 'ADULTO_MAYOR', 'DISCAPACIDAD']],
                    'estado' => ['Estado', 'enum', 'required', 'opciones' => ['ACTIVO', 'INACTIVO']],
                ],
            ],
            'dispositivos' => [
                'titulo' => 'Dispositivos', 'singular' => 'dispositivo', 'rf' => 'RF-021',
                'modelo' => Models\Dispositivo::class, 'orden' => 'codigo_inventario', 'con' => ['estacion', 'bus'],
                'uno_de' => ['id_estacion', 'id_bus'],
                'campos' => [
                    'codigo_inventario' => ['Código de inventario', 'text', 'required|max:100', 'unico' => true],
                    'tipo' => ['Tipo', 'enum', 'required', 'opciones' => ['TAT', 'TCP', 'CONTROL_ACCESO', 'LECTOR', 'OTRO']],
                    'id_estacion' => ['Estación', 'select', 'nullable', 'fuente' => [Models\Estacion::class, 'nombre'], 'mostrar' => 'estacion.nombre'],
                    'id_bus' => ['Bus', 'select', 'nullable', 'fuente' => [Models\Bus::class, 'numero_interno'], 'mostrar' => 'bus.numero_interno'],
                    'estado' => ['Estado', 'enum', 'required', 'opciones' => ['ACTIVO', 'INACTIVO', 'MANTENIMIENTO', 'DESCONECTADO']],
                    'fecha_instalacion' => ['Fecha de instalación', 'date', 'nullable|date'],
                ],
            ],
        ];
    }

    public static function obtener(string $clave): array
    {
        $catalogo = static::todos()[$clave] ?? abort(404);

        return $catalogo + ['clave' => $clave];
    }
}
