<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Estacion extends Model
{
    protected $table = 'estaciones';
    protected $primaryKey = 'id_estacion';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'ACTIVA', 'tiene_parqueo' => 'N'];

    public function municipalidad() { return $this->belongsTo(Municipalidad::class, 'id_municipalidad'); }

    public function lineas()
    {
        return $this->belongsToMany(Linea::class, 'linea_estacion', 'id_estacion', 'id_linea')
            ->withPivot('orden_estacion', 'distancia_desde_anterior');
    }

    public function accesos() { return $this->hasMany(Acceso::class, 'id_estacion'); }
    public function parqueos() { return $this->hasMany(Parqueo::class, 'id_estacion'); }
    public function dispositivos() { return $this->hasMany(Dispositivo::class, 'id_estacion'); }
    public function operadores() { return $this->hasMany(OperadorEstacion::class, 'id_estacion'); }
}
