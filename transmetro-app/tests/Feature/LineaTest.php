<?php

namespace Tests\Feature;

use App\Models\Estacion;
use Tests\TestCase;

/** RF-004, RF-005, RF-020, RN-001, RN-002, RN-008, RN-009 */
class LineaTest extends TestCase
{
    /** CP-017 RN-009 */
    public function test_distancia_total_es_la_suma_de_tramos(): void
    {
        $linea = $this->linea(4); // 3 tramos de 2 km

        $this->assertEquals(6.0, $linea->distanciaTotal());
    }

    /** CP-018 RN-001 */
    public function test_estacion_nueva_se_agrega_al_final_del_recorrido(): void
    {
        $linea = $this->linea(2);
        $nueva = Estacion::create(['nombre' => 'Final', 'id_municipalidad' => $linea->id_municipalidad]);

        $this->actingAs($this->usuario())
            ->post("/lineas/{$linea->id_linea}/estaciones", ['id_estacion' => $nueva->id_estacion, 'distancia_desde_anterior' => 3.5])
            ->assertSessionHas('ok');

        $ultima = $linea->estaciones()->get()->last();
        $this->assertEquals('Final', $ultima->nombre);
        $this->assertEquals(3, $ultima->pivot->orden_estacion);
        $this->assertEquals(5.5, $linea->fresh()->distanciaTotal());
    }

    /** CP-019 RN-009 */
    public function test_distancia_obligatoria_desde_la_segunda_estacion(): void
    {
        $linea = $this->linea(1);
        $nueva = Estacion::create(['nombre' => 'Sin distancia', 'id_municipalidad' => $linea->id_municipalidad]);

        $this->actingAs($this->usuario())
            ->post("/lineas/{$linea->id_linea}/estaciones", ['id_estacion' => $nueva->id_estacion])
            ->assertSessionHas('error');
        $this->assertEquals(1, $linea->estaciones()->count());
    }

    /** CP-020 RN-002 */
    public function test_una_estacion_puede_pertenecer_a_varias_lineas(): void
    {
        $l1 = $this->linea(2, 'A');
        $l2 = $this->linea(2, 'B');
        $compartida = $l1->estaciones->first();

        $this->actingAs($this->usuario())
            ->post("/lineas/{$l2->id_linea}/estaciones", ['id_estacion' => $compartida->id_estacion, 'distancia_desde_anterior' => 1])
            ->assertSessionHas('ok');

        $this->assertEquals(2, $compartida->lineas()->count());
    }

    /** CP-021 RN-002 RN-001 */
    public function test_quitar_estacion_reordena_y_respeta_pertenencia_minima(): void
    {
        $l1 = $this->linea(3, 'A');
        $l2 = $this->linea(1, 'B');
        $medio = $l1->estaciones[1];
        $l2->estaciones()->attach($medio->id_estacion, ['orden_estacion' => 2, 'distancia_desde_anterior' => 1]);
        $this->actingAs($this->usuario());

        // Pertenece a dos líneas: se puede quitar de una y el orden se compacta.
        $this->delete("/lineas/{$l1->id_linea}/estaciones/{$medio->id_estacion}")->assertSessionHas('ok');
        $this->assertEquals([1, 2], $l1->estaciones()->get()->pluck('pivot.orden_estacion')->all());

        // Ahora solo pertenece a una línea: no se puede dejar huérfana.
        $this->delete("/lineas/{$l2->id_linea}/estaciones/{$medio->id_estacion}")->assertSessionHas('error');
    }

    /** CP-022 RN-008 */
    public function test_linea_requiere_municipalidad(): void
    {
        $this->actingAs($this->usuario())
            ->post('/lineas', ['nombre' => 'Sin muni', 'estado' => 'ACTIVA'])
            ->assertSessionHasErrors('id_municipalidad');
    }
}
