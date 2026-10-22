<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperadorEstacion extends Model
{
    protected $table = 'operadores_estacion';
    protected $primaryKey = 'id_operador';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'ACTIVO'];

    protected $casts = ['fecha_asignacion' => 'date'];

    public function usuario() { return $this->belongsTo(Usuario::class, 'id_usuario'); }
    public function estacion() { return $this->belongsTo(Estacion::class, 'id_estacion'); }
}
