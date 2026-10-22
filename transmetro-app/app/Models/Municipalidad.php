<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Municipalidad extends Model
{
    protected $table = 'municipalidades';
    protected $primaryKey = 'id_municipalidad';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'ACTIVO'];

    public function lineas() { return $this->hasMany(Linea::class, 'id_municipalidad'); }
    public function estaciones() { return $this->hasMany(Estacion::class, 'id_municipalidad'); }
}
