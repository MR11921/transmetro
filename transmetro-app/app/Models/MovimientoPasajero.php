<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MovimientoPasajero extends Model
{
    protected $table = 'movimientos_pasajeros';
    protected $primaryKey = 'id_movimiento';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['fecha_hora' => 'datetime'];

    public function ticket() { return $this->belongsTo(Ticket::class, 'id_ticket'); }
    public function estacion() { return $this->belongsTo(Estacion::class, 'id_estacion'); }
    public function acceso() { return $this->belongsTo(Acceso::class, 'id_acceso'); }
}
