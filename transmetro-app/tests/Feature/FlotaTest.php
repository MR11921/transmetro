<?php

namespace Tests\Feature;

use App\Exceptions\ReglaNegocioException;
use App\Models\Bus;
use App\Models\BusLinea;
use App\Models\BusParqueo;
use App\Services\GestionFlota;
use Tests\TestCase;

/** RF-007, RF-008, RN-004, RN-005, RN-006 */
class FlotaTest extends TestCase
{
    /** CP-010 RN-006 */
    public function test_bus_nuevo_requiere_parqueo(): void
    {
        $this->actingAs($this->usuario('ADMINISTRADOR_TRANSPORTE'));

        $this->post('/buses', ['placa' => 'X1', 'numero_interno' => 'N1', 'capacidad_maxima' => 80, 'estado' => 'ACTIVO'])
            ->assertSessionHasErrors('id_parqueo');
        $this->assertDatabaseCount('buses', 0);

        $p = $this->parqueo();
        $this->post('/buses', ['placa' => 'X1', 'numero_interno' => 'N1', 'capacidad_maxima' => 80, 'estado' => 'ACTIVO', 'id_parqueo' => $p->id_parqueo])
            ->assertRedirect();
        $bus = Bus::first();
        $this->assertEquals($p->id_parqueo, $bus->parqueoActual()->id_parqueo);
        $this->assertEquals('OCUPADO', $p->fresh()->estado);
    }

    /** CP-011 RN-005 */
    public function test_bus_solo_tiene_una_linea_vigente(): void
    {
        $l1 = $this->linea(3, 'L1');
        $l2 = $this->linea(3, 'L2');
        $bus = $this->bus(100, $l1);

        $this->travel(1)->minutes();
        app(GestionFlota::class)->asignarLinea($bus, $l2);

        $this->assertEquals(1, BusLinea::where('id_bus', $bus->id_bus)->whereNull('fecha_fin')->count());
        $this->assertEquals($l2->id_linea, $bus->lineaActual()->id_linea);
        $this->assertEquals(2, BusLinea::where('id_bus', $bus->id_bus)->count(), 'Se conserva el historial');
    }

    /** CP-012 RN-004 */
    public function test_linea_no_supera_el_doble_de_sus_estaciones(): void
    {
        $linea = $this->linea(2); // máximo 4 buses
        for ($i = 0; $i < 4; $i++) {
            $this->bus(100, $linea);
        }
        $this->assertTrue($linea->cumpleCantidadBuses());

        $this->expectException(ReglaNegocioException::class);
        $this->bus(100, $linea);
    }

    /** CP-013 RN-004 */
    public function test_linea_con_menos_buses_que_estaciones_no_cumple(): void
    {
        $linea = $this->linea(3);
        $this->bus(100, $linea);
        $this->bus(100, $linea);

        $this->assertFalse($linea->cumpleCantidadBuses());
        $this->bus(100, $linea);
        $this->assertTrue($linea->fresh()->cumpleCantidadBuses());
    }

    /** CP-014 RN-006 RF-008 */
    public function test_cambio_de_parqueo_conserva_historial_y_libera_el_anterior(): void
    {
        $bus = $this->bus();
        $anterior = $bus->parqueoActual();
        $nuevo = $this->parqueo();

        $this->actingAs($this->usuario())->post("/buses/{$bus->id_bus}/parqueo", ['id_parqueo' => $nuevo->id_parqueo])->assertSessionHas('ok');

        $this->assertEquals($nuevo->id_parqueo, $bus->parqueoActual()->id_parqueo);
        $this->assertEquals(2, BusParqueo::where('id_bus', $bus->id_bus)->count());
        $this->assertEquals(1, BusParqueo::where('id_bus', $bus->id_bus)->whereNull('fecha_fin')->count());
        $this->assertEquals('DISPONIBLE', $anterior->fresh()->estado);
        $this->assertEquals('OCUPADO', $nuevo->fresh()->estado);
    }

    /** CP-015 */
    public function test_no_se_asigna_un_parqueo_ocupado(): void
    {
        $bus1 = $this->bus();
        $bus2 = $this->bus();

        $this->actingAs($this->usuario())->post("/buses/{$bus2->id_bus}/parqueo", ['id_parqueo' => $bus1->parqueoActual()->id_parqueo])
            ->assertSessionHas('error');
        $this->assertNotEquals($bus1->parqueoActual()->id_parqueo, $bus2->parqueoActual()->id_parqueo);
    }

    /** CP-016 */
    public function test_bus_en_mantenimiento_no_se_asigna_a_linea(): void
    {
        $bus = $this->bus();
        $bus->update(['estado' => 'MANTENIMIENTO']);

        $this->actingAs($this->usuario())->post("/buses/{$bus->id_bus}/linea", ['id_linea' => $this->linea()->id_linea])
            ->assertSessionHas('error');
        $this->assertNull($bus->lineaActual());
    }
}
