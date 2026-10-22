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
    }

    /** CP-028 RN-012 (límite exacto: 25 % no aplica) */
    public function test_sin_espera_adicional_con_25_por_ciento(): void
    {
        $r = $this->recorridoEnCurso(100);

        $this->tcp()->registrarParada($r, 25, 0);

        $this->assertFalse(Alerta::where('tipo_alerta', 'ESPERA_ADICIONAL')->exists());
    }

    /** CP-029 */
    public function test_no_se_supera_la_capacidad_del_bus(): void
    {
        $r = $this->recorridoEnCurso(50);

        $this->expectException(ReglaNegocioException::class);
        $this->tcp()->registrarParada($r, 51, 0);
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

        $this->actingAs($this->usuario('PILOTO'))
            ->post("/recorridos/{$r->id_recorrido}/paradas", ['pasajeros_abordan' => 60, 'pasajeros_descienden' => 0, 'en_espera' => 100])
            ->assertSessionHas('ok')->assertSessionHas('aviso');

        $this->assertDatabaseHas('control_pasajeros', ['id_recorrido' => $r->id_recorrido, 'ocupacion_calculada' => 60]);
        $this->assertDatabaseHas('auditoria', ['modulo' => 'TCP', 'accion' => 'REGISTRAR_PARADA']);
    }
}
