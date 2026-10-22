<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Parqueo extends Model
{
    protected $table = 'parqueos';
    protected $primaryKey = 'id_parqueo';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'DISPONIBLE'];

    /** Una estación con parqueos registrados queda marcada como estación con parqueo. */
    protected static function booted(): void
    {
        static::saved(fn (Parqueo $p) => Estacion::whereKey($p->id_estacion)->update(['tiene_parqueo' => 'S']));
    }

    public function estacion() { return $this->belongsTo(Estacion::class, 'id_estacion'); }
    public function historial() { return $this->hasMany(BusParqueo::class, 'id_parqueo'); }
}
