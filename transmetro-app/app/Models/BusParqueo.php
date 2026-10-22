<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusParqueo extends Model
{
    protected $table = 'bus_parqueo';
    protected $primaryKey = 'id_bus_parqueo';
    public $timestamps = false;
    protected $guarded = [];

    protected $casts = ['fecha_inicio' => 'datetime', 'fecha_fin' => 'datetime'];

    public function bus() { return $this->belongsTo(Bus::class, 'id_bus'); }
    public function parqueo() { return $this->belongsTo(Parqueo::class, 'id_parqueo'); }
}
