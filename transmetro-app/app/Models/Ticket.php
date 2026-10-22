<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $table = 'tickets';
    protected $primaryKey = 'id_ticket';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'VALIDO'];

    protected $casts = ['fecha_emision' => 'datetime', 'valor' => 'decimal:2'];

    public function pasajero() { return $this->belongsTo(Pasajero::class, 'id_pasajero'); }
    public function metodoPago() { return $this->belongsTo(MetodoPago::class, 'id_metodo_pago'); }
    public function movimientos() { return $this->hasMany(MovimientoPasajero::class, 'id_ticket'); }
}
