<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;

class Usuario extends Authenticatable
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;
    protected $guarded = [];
    protected $attributes = ['estado' => 'ACTIVO'];
    protected $hidden = ['password_hash'];
    protected $casts = ['fecha_creacion' => 'datetime'];

    /** RNF-002: la contraseña se guarda únicamente como hash. */
    public function getAuthPasswordName(): string
    {
        return 'password_hash';
    }

    public function rol() { return $this->belongsTo(Rol::class, 'id_rol'); }
    public function operaciones() { return $this->hasMany(OperadorEstacion::class, 'id_usuario'); }

    public function tieneRol(string ...$roles): bool
    {
        return in_array($this->rol?->nombre, $roles, true);
    }

    /** RN-015: ¿el rol del usuario puede usar el módulo indicado? */
    public function puede(string $modulo): bool
    {
        $roles = config("transmetro.permisos.$modulo", []);

        return in_array('*', $roles, true) || $this->tieneRol(...$roles);
    }

    public function getNombreCompletoAttribute(): string { return "{$this->nombre} {$this->apellido}"; }
}
