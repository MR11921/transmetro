<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bus extends Model
{
    protected $table = 'buses';
    protected $primaryKey = 'id_bus';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'ACTIVO'];

    public function asignacionesLinea() { return $this->hasMany(BusLinea::class, 'id_bus')->orderByDesc('fecha_inicio'); }
    public function historialParqueo() { return $this->hasMany(BusParqueo::class, 'id_bus')->orderByDesc('fecha_inicio'); }
    public function recorridos() { return $this->hasMany(Recorrido::class, 'id_bus'); }
    public function dispositivos() { return $this->hasMany(Dispositivo::class, 'id_bus'); }

    /** RN-005: como máximo una línea vigente. */
    public function lineaActual(): ?Linea
    {
        $asignacion = BusLinea::where('id_bus', $this->id_bus)->whereNull('fecha_fin')->first();

        return $asignacion ? Linea::find($asignacion->id_linea) : null;
    }

    /** RN-006: parqueo vigente. */
    public function parqueoActual(): ?Parqueo
    {
        $registro = BusParqueo::where('id_bus', $this->id_bus)->whereNull('fecha_fin')->first();

        return $registro?->parqueo;
    }

    public function recorridoEnCurso(): ?Recorrido
    {
        return $this->recorridos()->where('estado', 'EN_CURSO')->latest('fecha_inicio')->first();
    }
}
