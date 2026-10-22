<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Acceso extends Model
{
    protected $table = 'accesos';
    protected $primaryKey = 'id_acceso';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'ACTIVO'];

    public function estacion() { return $this->belongsTo(Estacion::class, 'id_estacion'); }

    /** Guardias con asignación vigente (sin fecha_fin). */
    public function guardias()
    {
        return $this->belongsToMany(Guardia::class, 'acceso_guardia', 'id_acceso', 'id_guardia')
            ->withPivot('fecha_inicio', 'fecha_fin')
            ->wherePivotNull('fecha_fin');
    }
}
