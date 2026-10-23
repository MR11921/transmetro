<?php

namespace App\Http\Controllers;

use App\Models\Linea;

/** RF-020 / RN-009: distancias entre estaciones consecutivas y total por línea. */
class DistanciaController extends Controller
{
    public function index()
    {
        return view('distancias.index', [
            'lineas' => Linea::with(['estaciones', 'municipalidad'])->orderBy('nombre')->get(),
        ]);
    }
}
