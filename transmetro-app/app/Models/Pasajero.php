<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pasajero extends Model
{
    protected $table = 'pasajeros';
    protected $primaryKey = 'id_pasajero';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'ACTIVO'];

    public function tickets() { return $this->hasMany(Ticket::class, 'id_pasajero'); }
}
