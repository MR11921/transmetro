<?php

namespace Tests\Feature;

use App\Models\Acceso;
use App\Models\Estacion;
use App\Models\Guardia;
use App\Models\MetodoPago;
use App\Models\Municipalidad;
use App\Models\Ticket;
use App\Services\ControlAcceso;
use Tests\TestCase;

/** RF-006, RF-011, RF-012, RF-013, RF-014, RN-003, RN-007, RN-013 */
class AccesoPasajerosTest extends TestCase
{
    private Acceso $acceso;
    private MetodoPago $metodo;

    protected function setUp(): void
    {
        parent::setUp();
        $muni = Municipalidad::create(['nombre' => 'Guatemala']);
        $estacion = Estacion::create(['nombre' => 'Trébol', 'id_municipalidad' => $muni->id_municipalidad]);
        $this->acceso = Acceso::create(['id_estacion' => $estacion->id_estacion, 'nombre' => 'Norte']);
        $this->metodo = MetodoPago::create(['nombre' => 'Efectivo']);
    }

    private function control(): ControlAcceso
    {
        return app(ControlAcceso::class);
    }

    /** CP-033 RF-012 */
    public function test_tat_emite_ticket_valido_con_codigo_unico(): void
    {
        $this->actingAs($this->usuario('OPERADOR_ESTACION'))
            ->post('/tat', ['id_metodo_pago' => $this->metodo->id_metodo_pago, 'cantidad' => 3])
            ->assertRedirect('/tat');

        $this->assertEquals(3, Ticket::where('estado', 'VALIDO')->count());
        $this->assertEquals(3, Ticket::distinct()->count('codigo_ticket'));
        $this->assertDatabaseHas('auditoria', ['modulo' => 'TAT', 'accion' => 'EMITIR_TICKET']);
    }

    /** CP-034 */
    public function test_metodo_de_pago_inactivo_no_emite_tickets(): void
    {
        $this->metodo->update(['estado' => 'INACTIVO']);

        $this->actingAs($this->usuario())
            ->post('/tat', ['id_metodo_pago' => $this->metodo->id_metodo_pago, 'cantidad' => 1])
            ->assertSessionHas('error');
        $this->assertDatabaseCount('tickets', 0);
    }

    /** CP-035 RN-013 RF-014 */
    public function test_entrada_valida_marca_el_ticket_como_utilizado(): void
    {
        $t = $this->control()->emitirTicket($this->metodo->id_metodo_pago);

        $r = $this->control()->validar($t->codigo_ticket, $this->acceso->id_acceso, 'ENTRADA');

        $this->assertTrue($r['autorizado']);
        $this->assertEquals('UTILIZADO', $t->fresh()->estado);
        $this->assertDatabaseHas('movimientos_pasajeros', [
            'id_ticket' => $t->id_ticket, 'id_acceso' => $this->acceso->id_acceso, 'tipo_movimiento' => 'ENTRADA', 'resultado' => 'VALIDADO',
        ]);
    }

    /** CP-036 RN-013: un ticket no se puede reutilizar */
    public function test_ticket_utilizado_es_rechazado(): void
    {
        $t = $this->control()->emitirTicket($this->metodo->id_metodo_pago);
        $this->control()->validar($t->codigo_ticket, $this->acceso->id_acceso, 'ENTRADA');

        $r = $this->control()->validar($t->codigo_ticket, $this->acceso->id_acceso, 'ENTRADA');

        $this->assertFalse($r['autorizado']);
        $this->assertDatabaseHas('movimientos_pasajeros', ['id_ticket' => $t->id_ticket, 'resultado' => 'RECHAZADO']);
    }

    /** CP-037 */
    public function test_ticket_inexistente_o_anulado_es_rechazado(): void
    {
        $this->assertFalse($this->control()->validar('TM-FALSO', $this->acceso->id_acceso, 'ENTRADA')['autorizado']);

        $t = $this->control()->emitirTicket($this->metodo->id_metodo_pago);
        $this->actingAs($this->usuario())->patch("/tickets/{$t->id_ticket}/anular")->assertSessionHas('ok');

        $this->assertFalse($this->control()->validar($t->codigo_ticket, $this->acceso->id_acceso, 'ENTRADA')['autorizado']);
        $this->assertEquals(2, \App\Models\MovimientoPasajero::where('resultado', 'RECHAZADO')->count());
    }

    /** CP-038 RF-013 */
    public function test_salida_requiere_entrada_previa_y_es_unica(): void
    {
        $t = $this->control()->emitirTicket($this->metodo->id_metodo_pago);

        $this->assertFalse($this->control()->validar($t->codigo_ticket, $this->acceso->id_acceso, 'SALIDA')['autorizado']);

        $this->control()->validar($t->codigo_ticket, $this->acceso->id_acceso, 'ENTRADA');
        $this->assertTrue($this->control()->validar($t->codigo_ticket, $this->acceso->id_acceso, 'SALIDA')['autorizado']);
        $this->assertFalse($this->control()->validar($t->codigo_ticket, $this->acceso->id_acceso, 'SALIDA')['autorizado']);
    }

    /** CP-039 */
    public function test_acceso_en_mantenimiento_rechaza_el_paso(): void
    {
        $t = $this->control()->emitirTicket($this->metodo->id_metodo_pago);
        $this->acceso->update(['estado' => 'MANTENIMIENTO']);

        $this->assertFalse($this->control()->validar($t->codigo_ticket, $this->acceso->id_acceso, 'ENTRADA')['autorizado']);
        $this->assertEquals('VALIDO', $t->fresh()->estado);
    }

    /** CP-040 flujo HTTP con rol guardia */
    public function test_guardia_valida_ticket_desde_control_de_acceso(): void
    {
        $t = $this->control()->emitirTicket($this->metodo->id_metodo_pago);

        $this->actingAs($this->usuario('GUARDIA'))
            ->post('/control-acceso', ['codigo_ticket' => strtolower($t->codigo_ticket), 'id_acceso' => $this->acceso->id_acceso, 'tipo_movimiento' => 'ENTRADA'])
            ->assertSessionHas('ok');
    }

    /** CP-041 RN-007 */
    public function test_acceso_activo_no_puede_quedar_sin_guardia(): void
    {
        $g1 = Guardia::create(['nombre' => 'Uno', 'apellido' => 'G']);
        $g2 = Guardia::create(['nombre' => 'Dos', 'apellido' => 'G']);
        $this->actingAs($this->usuario());

        $this->post("/seguridad/{$this->acceso->id_acceso}", ['id_guardia' => $g1->id_guardia])->assertSessionHas('ok');
        $this->delete("/seguridad/{$this->acceso->id_acceso}/{$g1->id_guardia}")->assertSessionHas('error');

        $this->travel(1)->seconds();
        $this->post("/seguridad/{$this->acceso->id_acceso}", ['id_guardia' => $g2->id_guardia])->assertSessionHas('ok');
        $this->delete("/seguridad/{$this->acceso->id_acceso}/{$g1->id_guardia}")->assertSessionHas('ok');

        $this->assertEquals([$g2->id_guardia], $this->acceso->guardias()->pluck('guardias.id_guardia')->all());
    }
}
