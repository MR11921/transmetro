<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispositivo extends Model
{
    protected $table = 'dispositivos';
    protected $primaryKey = 'id_dispositivo';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'ACTIVO'];

    protected $casts = ['fecha_instalacion' => 'date'];

    /** RF-021 / RF-017: una desconexión o error del dispositivo genera una alerta. */
    protected static function booted(): void
    {
        static::updated(function (Dispositivo $d) {
            if ($d->wasChanged('estado') && $d->estado === 'DESCONECTADO') {
                Alerta::create([
                    'id_bus' => $d->id_bus,
                    'id_estacion' => $d->id_estacion,
                    'tipo_alerta' => 'FALLA_COMUNICACION',
                    'nivel' => 'CRITICA',
                    'descripcion' => "El dispositivo {$d->tipo} {$d->codigo_inventario} perdió comunicación con la plataforma central.",
                    'fecha_hora' => now(),
                ]);
            }
        });
    }

    public function estacion() { return $this->belongsTo(Estacion::class, 'id_estacion'); }
    public function bus() { return $this->belongsTo(Bus::class, 'id_bus'); }

    public function getUbicacionAttribute(): string
    {
        return $this->estacion ? 'Estación '.$this->estacion->nombre : ($this->bus ? 'Bus '.$this->bus->numero_interno : '—');
    }
}
