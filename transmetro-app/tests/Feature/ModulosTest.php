<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Los 7 módulos del análisis de requerimientos · RF-001, RF-016, RF-020, RF-025, RN-015 */
class ModulosTest extends TestCase
{
    /** CP-051 */
    public function test_menu_muestra_los_siete_modulos_al_administrador(): void
    {
        $respuesta = $this->actingAs($this->usuario())->get('/');

        $respuesta->assertOk();
        for ($n = 1; $n <= 7; $n++) {
            $respuesta->assertSee("Módulo {$n}");
        }
    }

    /** CP-052 */
    public function test_menu_del_piloto_solo_muestra_sus_modulos(): void
    {
        $respuesta = $this->actingAs($this->usuario('PILOTO'))->get('/');

        $respuesta->assertOk()
            ->assertSee('Ocupación y TCP')
            ->assertSee('Incidentes')
            ->assertDontSee('Usuarios')
            ->assertDontSee('Terminal de autoservicio')
            ->assertDontSee('Auditoría');
    }

    /** CP-053 */
    public function test_matriz_de_roles_solo_para_administrador(): void
    {
        $this->actingAs($this->usuario())->get('/roles')->assertOk()->assertSee('Matriz de acceso por módulo');
        $this->actingAs($this->usuario('ADMINISTRADOR_TRANSPORTE'))->get('/roles')->assertForbidden();
    }

    /** CP-054 */
    public function test_ocupacion_muestra_buses_en_ruta(): void
    {
        $recorrido = $this->recorridoEnCurso();

        $this->actingAs($this->usuario('PILOTO'))->get('/ocupacion')
            ->assertOk()
            ->assertSee('Bus '.$recorrido->bus->numero_interno)
            ->assertSee('Abrir TCP');
        $this->actingAs($this->usuario('GUARDIA'))->get('/ocupacion')->assertForbidden();
    }

    /** CP-055 */
    public function test_distancias_muestra_total_por_linea(): void
    {
        $linea = $this->linea(3, 'Línea 12');

        $this->actingAs($this->usuario('ADMINISTRADOR_TRANSPORTE'))->get('/distancias')
            ->assertOk()
            ->assertSee('Línea 12')
            ->assertSee(number_format($linea->distanciaTotal(), 2).' km');
    }

    /** CP-057 */
    public function test_aviso_de_version_local_solo_fuera_de_produccion(): void
    {
        $usuario = $this->usuario();

        $this->app['env'] = 'local';
        $this->actingAs($usuario)->get('/')->assertSee('Versión local')->assertSee('[LOCAL]');

        $this->app['env'] = 'production';
        $this->actingAs($usuario)->get('/')->assertDontSee('Versión local')->assertDontSee('[LOCAL]');
    }

    /** CP-056 */
    public function test_consulta_integral_de_una_linea(): void
    {
        $linea = $this->linea(2, 'Línea 7');

        $this->actingAs($this->usuario('OPERADOR_ESTACION'))->get('/consulta?linea='.$linea->id_linea)
            ->assertOk()
            ->assertSee('Estaciones y accesos')
            ->assertSee($linea->estaciones->first()->nombre);
        $this->actingAs($this->usuario('PILOTO'))->get('/consulta')->assertForbidden();
    }
}
