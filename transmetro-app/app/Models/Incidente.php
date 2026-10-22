<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incidente extends Model
{
    protected $table = 'incidentes';
    protected $primaryKey = 'id_incidente';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'ABIERTO'];

    protected $casts = ['fecha_hora' => 'datetime'];

    public function bus() { return $this->belongsTo(Bus::class, 'id_bus'); }
    public function estacion() { return $this->belongsTo(Estacion::class, 'id_estacion'); }
    public function dispositivo() { return $this->belongsTo(Dispositivo::class, 'id_dispositivo'); }
    public function usuario() { return $this->belongsTo(Usuario::class, 'id_usuario_registro'); }
}
