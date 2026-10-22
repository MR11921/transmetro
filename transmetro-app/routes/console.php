<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Primer despliegue en la nube: carga los datos de demostración solo si la base está vacía.
Artisan::command('transmetro:sembrar-si-vacia', function () {
    if (\App\Models\Usuario::exists()) {
        $this->info('La base ya tiene datos; no se siembra.');

        return;
    }
    $this->call('db:seed', ['--force' => true]);
})->purpose('Siembra los datos de demostración si no hay usuarios');
