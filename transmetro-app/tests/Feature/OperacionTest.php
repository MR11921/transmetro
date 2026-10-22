<?php

namespace Tests\Feature;

use App\Models\Alerta;
use App\Models\Dispositivo;
use App\Models\Estacion;
use App\Models\Incidente;
use App\Models\Municipalidad;
use Tests\TestCase;

/** RF-003, RF-009, RF-010, RF-017, RF-021, RF-022, RF-023, RF-024, RF-025 */
class OperacionTest extends TestCase
{
    /** CP-042 RF-021 */
    public function test_dispositivo_desconectado_genera_alerta_critica(): void
    {
        $bus = $this->bus();
        $tcp = Dispositivo::create(['id_bus' => $bus->id_bus, 'codigo_inventario' => 'TCP-1', 'tipo' => 'TCP']);

        $this->actingAs($this->usuario())->put("/catalogos/dispositivos/{$tcp->id_dispositivo}", [
            'codigo_inventario' => 'TCP-1', 'tipo' => 'TCP', 'id_bus' => $bus->id_bus, 'estado' => 'DESCONECTADO',
        ])->assertRedirect();

        $this->assertDatabaseHas('alertas', ['tipo_alerta' => 'FALLA_COMUNICACION', 'nivel' => 'CRITICA', 'id_bus' => $bus->id_bus]);
    }

    /** CP-043 */
    public function test_dispositivo_debe_estar_en_estacion_o_en_bus_pero_no_ambos(): void
    {
        $bus = $this->bus();
        $estacion = Estacion::first();
        $this->actingAs($this->usuario());

        $base = ['codigo_inventario' => 'X-1', 'tipo' => 'LECTOR', 'estado' => 'ACTIVO'];
        $this->post('/catalogos/dispositivos', $base)->assertSessionHasErrors('id_estacion');
        $this->post('/catalogos/dispositivos', $base + ['id_bus' => $bus->id_bus, 'id_estacion' => $estacion->id_estacion])->assertSessionHasErrors('id_estacion');
        $this->post('/catalogos/dispositivos', $base + ['id_estacion' => $estacion->id_estacion])->assertSessionHasNoErrors();
    }

    /** CP-044 RF-017 */
    public function test_alerta_se_atiende_una_sola_vez(): void
    {
        $alerta = Alerta::create(['tipo_alerta' => 'INCIDENTE', 'descripcion' => 'Prueba', 'fecha_hora' => now()]);
        $this->actingAs($this->usuario('OPERADOR_ESTACION'));

        $this->patch("/alertas/{$alerta->id_alerta}", ['estado' => 'ATENDIDA'])->assertSessionHas('ok');
        $this->patch("/alertas/{$alerta->id_alerta}", ['estado' => 'CANCELADA'])->assertSessionHas('error');
        $this->assertEquals('ATENDIDA', $alerta->fresh()->estado);
    }

    /** CP-045 RF-022 */
    public function test_registro_y_cierre_de_incidente(): void
    {
        $user = $this->usuario('GUARDIA');
        $this->actingAs($user)->post('/incidentes', ['tipo_incidente' => 'SEGURIDAD', 'descripcion' => 'Persona sin ticket forzó el torniquete'])
            ->assertRedirect('/incidentes');

        $i = Incidente::first();
        $this->assertEquals($user->id_usuario, $i->id_usuario_registro);
        $this->assertEquals('ABIERTO', $i->estado);

        $this->actingAs($this->usuario('OPERADOR_ESTACION'))->patch("/incidentes/{$i->id_incidente}", ['estado' => 'CERRADO']);
        $this->assertEquals('CERRADO', $i->fresh()->estado);
    }

    /** CP-046 RF-003 RF-024 */
    public function test_catalogo_valida_unicidad_y_audita_cambios(): void
    {
        $this->actingAs($this->usuario());

        $this->post('/catalogos/municipalidades', ['nombre' => 'Mixco', 'estado' => 'ACTIVO'])->assertRedirect();
        $this->post('/catalogos/municipalidades', ['nombre' => 'Mixco', 'estado' => 'ACTIVO'])->assertSessionHasErrors('nombre');

        $m = Municipalidad::where('nombre', 'Mixco')->first();
        $this->put("/catalogos/municipalidades/{$m->id_municipalidad}", ['nombre' => 'Mixco', 'departamento' => 'Guatemala', 'estado' => 'ACTIVO'])->assertRedirect();

        $this->assertDatabaseHas('auditoria', ['modulo' => 'Municipalidades', 'accion' => 'CREAR', 'id_registro_afectado' => $m->id_municipalidad]);
        $this->assertDatabaseHas('auditoria', ['modulo' => 'Municipalidades', 'accion' => 'MODIFICAR']);
    }

    /** CP-047 RF-023 */
    public function test_reportes_se_consultan_y_exportan_en_csv(): void
    {
        $this->linea(3);
        $this->actingAs($this->usuario('ADMINISTRADOR_TRANSPORTE'));

        foreach (array_keys(\App\Http\Controllers\ReporteController::TIPOS) as $tipo) {
            $this->get("/reportes/{$tipo}")->assertOk();
        }

        $csv = $this->get('/reportes/lineas?formato=csv');
        $csv->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Línea Prueba', $csv->streamedContent());
    }

    /** CP-048 RF-025 */
    public function test_panel_y_consulta_integral_de_linea(): void
    {
        $r = $this->recorridoEnCurso();
        $this->actingAs($this->usuario());

        $this->get('/')->assertOk()->assertSee('Buses en ruta')->assertSee($r->bus->numero_interno);
        $this->get("/lineas/{$r->id_linea}")->assertOk()->assertSee('Recorrido de estaciones');
    }

    /** CP-049 RF-009 */
    public function test_registro_de_piloto_con_datos_personales_y_formacion(): void
    {
        $this->actingAs($this->usuario('ADMINISTRADOR_TRANSPORTE'))->post('/catalogos/pilotos', [
            'nombre' => 'Rosa', 'apellido' => 'Tzul', 'telefono' => '55554444', 'correo' => 'rosa@transmetro.test',
            'direccion' => 'Zona 7, Guatemala', 'formacion_academica' => 'Bachillerato en Ciencias y Letras', 'estado' => 'ACTIVO',
        ])->assertRedirect('/catalogos/pilotos');

        $this->assertDatabaseHas('pilotos', ['apellido' => 'Tzul', 'direccion' => 'Zona 7, Guatemala', 'formacion_academica' => 'Bachillerato en Ciencias y Letras']);

        $this->post('/catalogos/pilotos', ['nombre' => 'X', 'apellido' => 'Y', 'correo' => 'no-es-correo', 'estado' => 'ACTIVO'])->assertSessionHasErrors('correo');
    }

    /** CP-050 RF-010 */
    public function test_solo_usuarios_operadores_se_asignan_a_estaciones(): void
    {
        $estacion = $this->linea(1)->estaciones->first();
        $operador = $this->usuario('OPERADOR_ESTACION');
        $guardia = $this->usuario('GUARDIA');
        $this->actingAs($this->usuario());

        $this->post('/operadores', ['id_usuario' => $operador->id_usuario, 'id_estacion' => $estacion->id_estacion])->assertSessionHas('ok');
        $this->post('/operadores', ['id_usuario' => $operador->id_usuario, 'id_estacion' => $estacion->id_estacion])->assertSessionHas('error');
        $this->post('/operadores', ['id_usuario' => $guardia->id_usuario, 'id_estacion' => $estacion->id_estacion])->assertSessionHas('error');

        $this->assertDatabaseCount('operadores_estacion', 1);
    }
}
