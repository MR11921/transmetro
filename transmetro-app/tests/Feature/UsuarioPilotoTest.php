<?php

namespace Tests\Feature;

use App\Models\Piloto;
use App\Models\Rol;
use App\Models\Usuario;
use Tests\TestCase;

/** RF-001 · RN-015: al crear un usuario con rol Piloto se crea o vincula su registro de piloto. */
class UsuarioPilotoTest extends TestCase
{
    private function datos(string $rol, array $extra = []): array
    {
        return [
            'id_rol' => Rol::where('nombre', $rol)->value('id_rol'), 'nombre' => 'Jorge', 'apellido' => 'Barrios',
            'usuario' => 'jbarrios', 'correo' => 'jbarrios@muni.com', 'estado' => 'ACTIVO',
            'password' => 'Clave1234', 'password_confirmation' => 'Clave1234', ...$extra,
        ];
    }

    /** CP-062 */
    public function test_usuario_piloto_crea_su_piloto_automaticamente(): void
    {
        $this->actingAs($this->usuario())->post('/usuarios', $this->datos('PILOTO', ['id_piloto' => 'nuevo']))->assertSessionHasNoErrors();

        $usuario = Usuario::where('usuario', 'jbarrios')->first();
        $this->assertDatabaseHas('pilotos', ['nombre' => 'Jorge', 'apellido' => 'Barrios', 'correo' => 'jbarrios@muni.com', 'id_usuario' => $usuario->id_usuario]);
    }

    /** CP-063 */
    public function test_usuario_piloto_se_vincula_a_un_piloto_existente(): void
    {
        $maria = Piloto::create(['nombre' => 'María', 'apellido' => 'Gómez']);

        $this->actingAs($this->usuario())->post('/usuarios', $this->datos('PILOTO', ['id_piloto' => $maria->id_piloto]))->assertSessionHasNoErrors();

        $this->assertSame(Usuario::where('usuario', 'jbarrios')->value('id_usuario'), $maria->fresh()->id_usuario);
        $this->assertSame(1, Piloto::count());
    }

    /** CP-064 */
    public function test_no_se_vincula_un_piloto_que_ya_tiene_usuario(): void
    {
        $otro = $this->usuario('PILOTO');
        $maria = Piloto::create(['nombre' => 'María', 'apellido' => 'Gómez', 'id_usuario' => $otro->id_usuario]);

        $this->actingAs($this->usuario())->post('/usuarios', $this->datos('PILOTO', ['id_piloto' => $maria->id_piloto]))->assertSessionHasErrors('id_piloto');

        $this->assertSame($otro->id_usuario, $maria->fresh()->id_usuario);
    }

    /** CP-065 */
    public function test_otros_roles_no_crean_piloto(): void
    {
        $this->actingAs($this->usuario())->post('/usuarios', $this->datos('GUARDIA', ['id_piloto' => 'nuevo']))->assertSessionHasNoErrors();

        $this->assertSame(0, Piloto::count());
    }

    /** CP-066 */
    public function test_cambiar_el_rol_de_piloto_libera_el_vinculo(): void
    {
        $this->actingAs($this->usuario())->post('/usuarios', $this->datos('PILOTO', ['id_piloto' => 'nuevo']));
        $usuario = Usuario::where('usuario', 'jbarrios')->first();

        $this->put("/usuarios/{$usuario->id_usuario}", [...$this->datos('GUARDIA'), 'password' => '', 'password_confirmation' => ''])->assertSessionHasNoErrors();

        $this->assertNull(Piloto::first()->id_usuario);
    }

    /** CP-067 */
    public function test_formulario_muestra_el_bloque_piloto(): void
    {
        Piloto::create(['nombre' => 'María', 'apellido' => 'Gómez']);

        $this->actingAs($this->usuario())->get('/usuarios/create')->assertOk()
            ->assertSee('Este usuario conduce como')->assertSee('Crear un piloto nuevo')->assertSee('María Gómez');
    }
}
