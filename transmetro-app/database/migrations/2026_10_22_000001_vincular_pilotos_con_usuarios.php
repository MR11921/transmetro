<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * RN-015: cada piloto puede tener un usuario del sistema; con él, el piloto solo
 * opera en el TCP los recorridos que tiene asignados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pilotos', function (Blueprint $t) {
            $t->foreignId('id_usuario')->nullable()->unique('uq_pilotos_usuario')->constrained('usuarios', 'id_usuario');
        });

        // Vincula automáticamente los usuarios con rol PILOTO al piloto con el mismo nombre y apellido.
        $usuarios = DB::table('usuarios')
            ->join('roles', 'roles.id_rol', '=', 'usuarios.id_rol')
            ->where('roles.nombre', 'PILOTO')
            ->get(['usuarios.id_usuario', 'usuarios.nombre', 'usuarios.apellido']);

        foreach ($usuarios as $u) {
            $piloto = DB::table('pilotos')->whereNull('id_usuario')
                ->where('nombre', $u->nombre)->where('apellido', $u->apellido)->value('id_piloto');

            if ($piloto) {
                DB::table('pilotos')->where('id_piloto', $piloto)->update(['id_usuario' => $u->id_usuario]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('pilotos', function (Blueprint $t) {
            $t->dropForeign(['id_usuario']);
            $t->dropUnique('uq_pilotos_usuario');
            $t->dropColumn('id_usuario');
        });
    }
};
