<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusLinea extends Model
{
    protected $table = 'bus_linea';
    public $incrementing = false;
    protected $primaryKey = 'id_bus';
    public $timestamps = false;
    protected $guarded = [];
    protected $casts = ['fecha_inicio' => 'datetime', 'fecha_fin' => 'datetime'];

    public function bus() { return $this->belongsTo(Bus::class, 'id_bus'); }
    public function linea() { return $this->belongsTo(Linea::class, 'id_linea'); }
}
