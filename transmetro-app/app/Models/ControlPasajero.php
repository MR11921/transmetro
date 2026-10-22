<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ControlPasajero extends Model
{
    protected $table = 'control_pasajeros';
    protected $primaryKey = 'id_control';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['fecha_hora' => 'datetime'];

    public function recorrido() { return $this->belongsTo(Recorrido::class, 'id_recorrido'); }
    public function estacion() { return $this->belongsTo(Estacion::class, 'id_estacion'); }
}
