<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Linea extends Model
{
    protected $table = 'lineas';
    protected $primaryKey = 'id_linea';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'ACTIVA'];

    public function municipalidad() { return $this->belongsTo(Municipalidad::class, 'id_municipalidad'); }

    /** RN-001: estaciones en el orden del recorrido. */
    public function estaciones()
    {
        return $this->belongsToMany(Estacion::class, 'linea_estacion', 'id_linea', 'id_estacion')
            ->withPivot('orden_estacion', 'distancia_desde_anterior')
            ->orderByPivot('orden_estacion');
    }

    /** Buses con asignación vigente a la línea. */
    public function buses()
    {
        return $this->belongsToMany(Bus::class, 'bus_linea', 'id_linea', 'id_bus')
            ->withPivot('fecha_inicio', 'fecha_fin')
            ->wherePivotNull('fecha_fin');
    }

    public function recorridos() { return $this->hasMany(Recorrido::class, 'id_linea'); }

    /** RN-009 / RF-020: distancia total en km. */
    public function distanciaTotal(): float
    {
        return (float) $this->estaciones->sum(fn ($e) => $e->pivot->distancia_desde_anterior ?? 0);
    }

    /** RN-004: mínimo = nº de estaciones, máximo = el doble. */
    public function minimoBuses(): int { return $this->estaciones()->count(); }
    public function maximoBuses(): int { return $this->minimoBuses() * 2; }

    public function cumpleCantidadBuses(): bool
    {
        $n = $this->buses()->count();

        return $n >= $this->minimoBuses() && $n <= $this->maximoBuses();
    }
}
