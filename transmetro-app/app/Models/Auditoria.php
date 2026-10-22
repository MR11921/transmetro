<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Auditoria extends Model
{
    protected $table = 'auditoria';
    protected $primaryKey = 'id_auditoria';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['fecha_hora' => 'datetime'];

    public function usuario() { return $this->belongsTo(Usuario::class, 'id_usuario'); }
}
