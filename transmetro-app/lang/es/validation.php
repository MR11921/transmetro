<?php

return [
    'between' => ['numeric' => 'El campo :attribute debe estar entre :min y :max.'],
    'confirmed' => 'La confirmación de :attribute no coincide.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'email' => 'El campo :attribute debe ser un correo válido.',
    'exists' => 'El :attribute seleccionado no es válido.',
    'in' => 'El :attribute seleccionado no es válido.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'max' => ['numeric' => 'El campo :attribute no debe ser mayor que :max.', 'string' => 'El campo :attribute no debe superar :max caracteres.'],
    'min' => ['numeric' => 'El campo :attribute debe ser al menos :min.', 'string' => 'El campo :attribute debe tener al menos :min caracteres.'],
    'numeric' => 'El campo :attribute debe ser numérico.',
    'password' => [
        'letters' => 'La :attribute debe contener al menos una letra.',
        'numbers' => 'La :attribute debe contener al menos un número.',
    ],
    'prohibited' => 'El campo :attribute no está permitido.',
    'regex' => 'El formato de :attribute no es válido (solo letras, números, punto, guion y guion bajo).',
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'unique' => 'Ya existe un registro con ese :attribute.',

    'attributes' => [
        'nombre' => 'nombre', 'apellido' => 'apellido', 'correo' => 'correo', 'usuario' => 'usuario',
        'estado' => 'estado', 'descripcion' => 'descripción', 'cantidad' => 'cantidad',
        'id_estacion' => 'estación', 'id_linea' => 'línea', 'id_guardia' => 'guardia', 'id_usuario' => 'usuario',
        'tipo_movimiento' => 'movimiento', 'placa' => 'placa', 'codigo' => 'código',
    ],
];
