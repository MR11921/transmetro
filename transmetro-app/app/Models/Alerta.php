<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Alerta extends Model
{
    protected $table = 'alertas';
    protected $primaryKey = 'id_alerta';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'PENDIENTE'];

    protected $casts = ['fecha_hora' => 'datetime'];

    public function bus() { return $this->belongsTo(Bus::class, 'id_bus'); }
    public function estacion() { return $this->belongsTo(Estacion::class, 'id_estacion'); }
    public function recorrido() { return $this->belongsTo(Recorrido::class, 'id_recorrido'); }
}
