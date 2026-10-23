<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Piloto extends Model
{
    protected $table = 'pilotos';
    protected $primaryKey = 'id_piloto';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'ACTIVO'];

    public function recorridos() { return $this->hasMany(Recorrido::class, 'id_piloto'); }
    public function usuario() { return $this->belongsTo(Usuario::class, 'id_usuario'); }

    public function getNombreCompletoAttribute(): string { return "{$this->nombre} {$this->apellido}"; }
}
