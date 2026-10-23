<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Alerta;
use App\Services\ControlOcupacion;
use Tests\TestCase;

/** RF-015, RF-016, RF-017, RF-018, RF-019, RN-010, RN-011, RN-012 */
class OcupacionTest extends TestCase
{
    private function tcp(): ControlOcupacion
    {
        return app(ControlOcupacion::class);
    }

    /** CP-023 RN-010 */
    public function test_ocupacion_se_calcula_con_abordajes_y_descensos(): void
    {
        $r = $this->recorridoEnCurso(100);

        $this->tcp()->registrarParada($r, 40, 0);
        $this->tcp()->registrarParada($r->fresh(), 20, 15);

        $this->assertEquals(45, $r->fresh()->ocupacionActual());
        $this->assertEquals(45.0, $r->fresh()->porcentajeOcupacion());
    }

    /** CP-024 RN-001 RF-019 */
    public function test_las_paradas_siguen_el_orden_de_la_linea(): void
    {
        $r = $this->recorridoEnCurso(100, 3);
        $orden = $r->linea->estaciones->pluck('id_estacion')->all();

        $this->tcp()->registrarParada($r, 30, 0);
        $this->tcp()->registrarParada($r->fresh(), 10, 5);
        $this->tcp()->registrarParada($r->fresh(), 0, 35);

        $this->assertEquals($orden, $r->controles()->pluck('id_estacion')->all());

        $this->expectException(ReglaNegocioException::class);
        $this->tcp()->registrarParada($r->fresh(), 1, 0);
    }

    /** CP-025 RN-011 (límite exacto: 150 %) */
    public function test_alerta_de_alta_demanda_al_alcanzar_150_por_ciento(): void
    {
        $r = $this->recorridoEnCurso(100);

        $this->tcp()->registrarParada($r, 100, 0, 50); // demanda 150 / 100

        $this->assertDatabaseHas('alertas', ['id_recorrido' => $r->id_recorrido, 'tipo_alerta' => 'ALTA_DEMANDA', 'estado' => 'PENDIENTE']);
    }

    /** CP-026 RN-011 (justo debajo del umbral) */
    public function test_sin_alerta_de_alta_demanda_al_149_por_ciento(): void
    {
        $r = $this->recorridoEnCurso(100);

        $this->tcp()->registrarParada($r, 100, 0, 49);

        $this->assertFalse(Alerta::where('tipo_alerta', 'ALTA_DEMANDA')->exists());
    }

    /** CP-027 RN-012 */
    public function test_espera_adicional_con_ocupacion_menor_al_25_por_ciento(): void
    {
        $r = $this->recorridoEnCurso(100);

        $this->tcp()->registrarParada($r, 24, 0);

        $alerta = Alerta::where('tipo_alerta', 'ESPERA_ADICIONAL')->first();
        $this->assertNotNull($alerta);
        $this->assertStringContainsString('5 minutos', $alerta->descripcion);
        $this->assertSame('PENDIENTE', $alerta->estado); // visible en Alertas y en el panel
    }

    /** CP-028 RN-012 (límite exacto: 25 % no aplica) */
    public function test_sin_espera_adicional_con_25_por_ciento(): void
    {
        $r = $this->recorridoEnCurso(100);

        $this->tcp()->registrarParada($r, 25, 0);

        $this->assertFalse(Alerta::where('tipo_alerta', 'ESPERA_ADICIONAL')->exists());
    }

    /** CP-029: nunca se supera la capacidad; quienes no caben quedan en el andén y el bus lleno genera alerta */
    public function test_no_se_supera_la_capacidad_del_bus(): void
    {
        $r = $this->recorridoEnCurso(50);

        $res = $this->tcp()->registrarParada($r, 60, 0);

        $this->assertSame(50, $r->fresh()->ocupacionActual());
        $this->assertSame(10, $res['sin_abordar']);
        $this->assertDatabaseHas('control_pasajeros', ['id_recorrido' => $r->id_recorrido, 'pasajeros_abordan' => 50, 'ocupacion_calculada' => 50]);
        $alerta = Alerta::where('tipo_alerta', 'BUS_LLENO')->first();
        $this->assertSame('PENDIENTE', $alerta?->estado);
        $this->assertStringContainsString('Quedaron 10 pasajeros', $alerta->descripcion);
        $this->assertFalse(Alerta::where('tipo_alerta', 'ALTA_DEMANDA')->exists()); // 60 / 50 = 120 %
    }

    /** CP-068: el bus lleno sin pasajeros en el andén también genera alerta */
    public function test_alerta_de_bus_lleno_al_100_por_ciento(): void
    {
        $r = $this->recorridoEnCurso(80);

        $this->tcp()->registrarParada($r, 80, 0);

        $this->assertDatabaseHas('alertas', ['id_recorrido' => $r->id_recorrido, 'tipo_alerta' => 'BUS_LLENO', 'nivel' => 'MEDIA', 'estado' => 'PENDIENTE']);
    }

    /** CP-069 RN-011: los que no caben cuentan para la demanda */
    public function test_exceso_de_abordaje_dispara_alta_demanda(): void
    {
        $r = $this->recorridoEnCurso(100);

        $this->tcp()->registrarParada($r, 150, 0); // 100 a bordo + 50 sin abordar = 150 %

        $this->assertSame(100, $r->fresh()->ocupacionActual());
        $this->assertDatabaseHas('alertas', ['tipo_alerta' => 'ALTA_DEMANDA', 'estado' => 'PENDIENTE']);
        $this->assertFalse(Alerta::where('tipo_alerta', 'BUS_LLENO')->exists()); // la de alta demanda ya lo cubre
    }

    /** CP-070 RN-012: en la última estación no se aplica espera adicional */
    public function test_sin_espera_adicional_en_la_ultima_estacion(): void
    {
        $r = $this->recorridoEnCurso(100, 2);

        $this->tcp()->registrarParada($r, 30, 0);
        $this->tcp()->registrarParada($r->fresh(), 0, 30);

        $this->assertFalse(Alerta::where('tipo_alerta', 'ESPERA_ADICIONAL')->exists());
    }

    /** CP-030 */
    public function test_no_descienden_mas_pasajeros_de_los_que_hay(): void
    {
        $r = $this->recorridoEnCurso(50);
        $this->tcp()->registrarParada($r, 10, 0);

        $this->expectException(ReglaNegocioException::class);
        $this->tcp()->registrarParada($r->fresh(), 0, 11);
    }

    /** CP-031 */
    public function test_solo_recorridos_en_curso_registran_pasajeros(): void
    {
        $r = $this->recorridoEnCurso();
        $r->update(['estado' => 'FINALIZADO']);

        $this->expectException(ReglaNegocioException::class);
        $this->tcp()->registrarParada($r, 1, 0);
    }

    /** CP-032 RF-019 flujo HTTP completo con rol piloto */
    public function test_piloto_registra_parada_desde_el_tcp(): void
    {
        $r = $this->recorridoEnCurso(100);
        $piloto = $this->usuario('PILOTO');
        $r->piloto->update(['id_usuario' => $piloto->id_usuario]);

        $this->actingAs($piloto)
            ->post("/recorridos/{$r->id_recorrido}/paradas", ['pasajeros_abordan' => 60, 'pasajeros_descienden' => 0, 'en_espera' => 100])
            ->assertSessionHas('ok')->assertSessionHas('aviso');

        $this->assertDatabaseHas('control_pasajeros', ['id_recorrido' => $r->id_recorrido, 'ocupacion_calculada' => 60]);
        $this->assertDatabaseHas('auditoria', ['modulo' => 'TCP', 'accion' => 'REGISTRAR_PARADA']);
    }

    /** CP-058 RN-015: un piloto no puede ver ni operar el recorrido asignado a otro piloto */
    public function test_piloto_no_opera_recorrido_de_otro_piloto(): void
    {
        $ajeno = $this->recorridoEnCurso(100);
        $propio = $this->recorridoEnCurso(100);
        $piloto = $this->usuario('PILOTO');
        $propio->piloto->update(['id_usuario' => $piloto->id_usuario]);
        $this->actingAs($piloto);

        $this->get("/recorridos/{$ajeno->id_recorrido}")->assertForbidden();
        $this->post("/recorridos/{$ajeno->id_recorrido}/paradas", ['pasajeros_abordan' => 5, 'pasajeros_descienden' => 0])->assertForbidden();
        $this->patch("/recorridos/{$ajeno->id_recorrido}/finalizar")->assertForbidden();
        $this->assertDatabaseMissing('control_pasajeros', ['id_recorrido' => $ajeno->id_recorrido]);

        $this->get("/recorridos/{$propio->id_recorrido}")->assertOk();
        $this->get('/recorridos')->assertOk()->assertSee($propio->bus->numero_interno)
            ->assertSee(route('recorridos.show', $propio))->assertDontSee(route('recorridos.show', $ajeno));
    }

    /** CP-059: el administrador sigue operando cualquier recorrido */
    public function test_administrador_opera_cualquier_recorrido(): void
    {
        $r = $this->recorridoEnCurso(100);

        $this->actingAs($this->usuario('ADMINISTRADOR'))->get("/recorridos/{$r->id_recorrido}")->assertOk();
    }

    /** CP-060: un usuario piloto sin vínculo no ve recorridos y recibe un aviso */
    public function test_piloto_sin_vinculo_no_ve_recorridos(): void
    {
        $r = $this->recorridoEnCurso(100);
        $this->actingAs($this->usuario('PILOTO'));

        $this->get('/recorridos')->assertOk()->assertSee('no está vinculado a un piloto')->assertDontSee(route('recorridos.show', $r));
        $this->get('/ocupacion')->assertOk()->assertSee('no está vinculado a un piloto');
    }

    /** CP-061: el administrador vincula un piloto con un usuario de rol PILOTO desde el catálogo */
    public function test_catalogo_vincula_piloto_con_usuario(): void
    {
        $piloto = \App\Models\Piloto::create(['nombre' => 'María', 'apellido' => 'Gómez']);
        $usuarioPiloto = $this->usuario('PILOTO');
        $guardia = $this->usuario('GUARDIA');
        $this->actingAs($this->usuario());

        $this->get("/catalogos/pilotos/{$piloto->id_piloto}/editar")->assertOk()
            ->assertSee('Usuario del sistema')
            ->assertSee('>'.$usuarioPiloto->usuario.'</option>', false)->assertDontSee('>'.$guardia->usuario.'</option>', false);

        $datos = ['nombre' => 'María', 'apellido' => 'Gómez', 'estado' => 'ACTIVO'];
        $this->put("/catalogos/pilotos/{$piloto->id_piloto}", [...$datos, 'id_usuario' => $guardia->id_usuario])->assertSessionHasErrors('id_usuario');
        $this->put("/catalogos/pilotos/{$piloto->id_piloto}", [...$datos, 'id_usuario' => $usuarioPiloto->id_usuario])->assertSessionHasNoErrors();

        $this->assertSame($usuarioPiloto->id_usuario, $piloto->fresh()->id_usuario);
    }
}
