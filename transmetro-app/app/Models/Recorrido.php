<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Recorrido extends Model
{
    protected $table = 'recorridos';
    protected $primaryKey = 'id_recorrido';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'PROGRAMADO'];
    protected $casts = ['fecha_inicio' => 'datetime', 'fecha_fin' => 'datetime'];

    public function bus() { return $this->belongsTo(Bus::class, 'id_bus'); }

    /** RN-015: un usuario con rol PILOTO solo ve y opera los recorridos que tiene asignados. */
    public function scopeVisiblesPara($query, Usuario $usuario)
    {
        return $usuario->tieneRol('PILOTO')
            ? $query->whereHas('piloto', fn ($q) => $q->where('id_usuario', $usuario->id_usuario))
            : $query;
    }

    public function esOperablePor(Usuario $usuario): bool
    {
        return ! $usuario->tieneRol('PILOTO') || $this->piloto?->id_usuario === $usuario->id_usuario;
    }

    public function linea() { return $this->belongsTo(Linea::class, 'id_linea'); }
    public function piloto() { return $this->belongsTo(Piloto::class, 'id_piloto'); }
    public function controles() { return $this->hasMany(ControlPasajero::class, 'id_recorrido')->orderBy('id_control'); }
    public function alertas() { return $this->hasMany(Alerta::class, 'id_recorrido'); }

    /** RN-010: ocupación según el último registro del TCP. */
    public function ocupacionActual(): int
    {
        return (int) ($this->controles()->reorder('id_control', 'desc')->value('ocupacion_calculada') ?? 0);
    }

    public function porcentajeOcupacion(): float
    {
        $capacidad = $this->bus->capacidad_maxima ?: 1;

        return round($this->ocupacionActual() * 100 / $capacidad, 1);
    }

    /** Siguiente estación según el orden de la línea (RN-001). */
    public function siguienteEstacion(): ?Estacion
    {
        $visitadas = $this->controles()->count();

        return $this->linea->estaciones->get($visitadas);
    }
}
