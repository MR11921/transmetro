<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Guardia extends Model
{
    protected $table = 'guardias';
    protected $primaryKey = 'id_guardia';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'ACTIVO'];

    public function accesos()
    {
        return $this->belongsToMany(Acceso::class, 'acceso_guardia', 'id_guardia', 'id_acceso')
            ->withPivot('fecha_inicio', 'fecha_fin')
            ->wherePivotNull('fecha_fin');
    }

    public function getNombreCompletoAttribute(): string { return "{$this->nombre} {$this->apellido}"; }
}
