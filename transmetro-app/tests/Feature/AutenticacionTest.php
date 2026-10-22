<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** RF-001, RF-002, RNF-001, RNF-002, RN-015 */
class AutenticacionTest extends TestCase
{
    /** CP-001 */
    public function test_usuario_activo_inicia_sesion_y_queda_auditado(): void
    {
        $u = $this->usuario('OPERADOR_ESTACION');

        $this->post('/login', ['usuario' => $u->usuario, 'password' => 'Clave1234'])->assertRedirect('/');

        $this->assertAuthenticatedAs($u);
        $this->assertDatabaseHas('auditoria', ['id_usuario' => $u->id_usuario, 'accion' => 'LOGIN']);
    }

    /** CP-002 */
    public function test_credenciales_invalidas_son_rechazadas(): void
    {
        $u = $this->usuario();

        $this->post('/login', ['usuario' => $u->usuario, 'password' => 'incorrecta'])->assertSessionHasErrors('usuario');

        $this->assertGuest();
        $this->assertDatabaseHas('auditoria', ['id_usuario' => $u->id_usuario, 'accion' => 'LOGIN_FALLIDO']);
    }

    /** CP-003 */
    public function test_usuario_inactivo_no_puede_ingresar(): void
    {
        $u = $this->usuario('ADMINISTRADOR', ['estado' => 'INACTIVO']);

        $this->post('/login', ['usuario' => $u->usuario, 'password' => 'Clave1234'])->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }

    /** CP-004 */
    public function test_bloqueo_tras_cinco_intentos_fallidos(): void
    {
        $u = $this->usuario();
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['usuario' => $u->usuario, 'password' => 'mala']);
        }

        $this->post('/login', ['usuario' => $u->usuario, 'password' => 'Clave1234'])->assertSessionHasErrors('usuario');
        $this->assertGuest();
    }

    /** CP-005 */
    public function test_visitante_es_redirigido_al_login(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/buses')->assertRedirect('/login');
    }

    /** CP-006 RN-015 */
    public function test_cada_rol_solo_accede_a_sus_modulos(): void
    {
        $this->actingAs($this->usuario('PILOTO'));
        $this->get('/recorridos')->assertOk();
        $this->get('/usuarios')->assertForbidden();
        $this->get('/buses')->assertForbidden();
        $this->get('/catalogos/municipalidades')->assertForbidden();

        $this->actingAs($this->usuario('GUARDIA'));
        $this->get('/control-acceso')->assertOk();
        $this->get('/tat')->assertForbidden();
        $this->get('/auditoria')->assertForbidden();

        $this->actingAs($this->usuario('ADMINISTRADOR_TRANSPORTE'));
        $this->get('/buses')->assertOk();
        $this->get('/usuarios')->assertForbidden();
    }

    /** CP-007 RF-001 RNF-002 */
    public function test_administrador_crea_usuario_con_contrasena_cifrada(): void
    {
        $this->actingAs($this->usuario());

        $this->post('/usuarios', [
            'id_rol' => Rol::where('nombre', 'OPERADOR_ESTACION')->value('id_rol'), 'nombre' => 'Nuevo', 'apellido' => 'Operador', 'usuario' => 'nuevo.op',
            'correo' => 'nuevo@transmetro.test', 'estado' => 'ACTIVO', 'password' => 'Segura123', 'password_confirmation' => 'Segura123',
        ])->assertRedirect('/usuarios');

        $u = Usuario::where('usuario', 'nuevo.op')->first();
        $this->assertNotEquals('Segura123', $u->password_hash);
        $this->assertTrue(Hash::check('Segura123', $u->password_hash));
    }

    /** CP-008 */
    public function test_contrasena_debil_es_rechazada(): void
    {
        $this->actingAs($this->usuario());

        $this->post('/usuarios', [
            'id_rol' => Rol::where('nombre', 'OPERADOR_ESTACION')->value('id_rol'), 'nombre' => 'A', 'apellido' => 'B', 'usuario' => 'debil', 'correo' => 'd@transmetro.test',
            'estado' => 'ACTIVO', 'password' => '123', 'password_confirmation' => '123',
        ])->assertSessionHasErrors('password');
    }

    /** CP-009 */
    public function test_desactivar_usuario_y_no_desactivarse_a_si_mismo(): void
    {
        $admin = $this->usuario();
        $otro = $this->usuario('GUARDIA');
        $this->actingAs($admin);

        $this->patch("/usuarios/{$otro->id_usuario}/estado")->assertRedirect();
        $this->assertEquals('INACTIVO', $otro->fresh()->estado);

        $this->patch("/usuarios/{$admin->id_usuario}/estado")->assertSessionHas('error');
        $this->assertEquals('ACTIVO', $admin->fresh()->estado);
        $this->assertTrue(Auditoria::where('accion', 'DESACTIVAR')->exists());
    }
}
