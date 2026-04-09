<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class UserApiTest extends TestCase
{
    public function test_admin_puede_listar_usuarios(): void
    {
        $admin = $this->createAdmin();
        $moderator = $this->createModerator();
        $this->actingAsApi($admin);

        $this->getJson('/api/v1/users')
            ->assertOk()
            ->assertJsonFragment(['id' => $moderator->id]);
    }

    public function test_listar_usuarios_falla_sin_permiso(): void
    {
        $this->actingAsApi($this->createUser());

        $this->getJson('/api/v1/users')->assertForbidden();
    }

    public function test_admin_puede_crear_usuario_con_roles(): void
    {
        $admin = $this->createAdmin();
        $this->actingAsApi($admin);

        $response = $this->postJson('/api/v1/users', [
            'name' => 'Nuevo Moderador',
            'email' => 'nuevo@example.com',
            'password' => 'password123',
            'roles' => ['moderator'],
            'is_active' => true,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.email', 'nuevo@example.com')
            ->assertJsonFragment(['moderator']);

        $this->assertDatabaseHas('users', ['email' => 'nuevo@example.com']);
    }

    public function test_crear_usuario_valida_email_password_y_roles(): void
    {
        $admin = $this->createAdmin();
        $existing = User::factory()->create(['email' => 'duplicado@example.com']);
        $this->actingAsApi($admin);

        $this->postJson('/api/v1/users', [
            'name' => 'Duplicado',
            'email' => $existing->email,
            'password' => '123',
            'roles' => ['rol-inexistente'],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password', 'roles.0']);
    }

    public function test_admin_puede_ver_detalle_de_usuario(): void
    {
        $admin = $this->createAdmin();
        $moderator = $this->createModerator(['email' => 'detalle@example.com']);
        $this->actingAsApi($admin);

        $this->getJson("/api/v1/users/{$moderator->id}")
            ->assertOk()
            ->assertJsonPath('data.email', 'detalle@example.com');
    }

    public function test_usuario_puede_actualizar_su_propio_perfil(): void
    {
        $moderator = $this->createModerator([
            'email' => 'antes@example.com',
            'name' => 'Antes',
        ]);
        $this->actingAsApi($moderator);

        $this->patchJson("/api/v1/users/{$moderator->id}", [
            'name' => 'Despues',
            'email' => 'despues@example.com',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Despues')
            ->assertJsonPath('data.email', 'despues@example.com');
    }

    public function test_admin_puede_actualizar_usuario_gestionable(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createModerator(['name' => 'Moderador']);
        $this->actingAsApi($admin);

        $this->patchJson("/api/v1/users/{$target->id}", [
            'name' => 'Moderador Actualizado',
            'is_active' => false,
        ])->assertOk()
            ->assertJsonPath('data.name', 'Moderador Actualizado')
            ->assertJsonPath('data.is_active', false);
    }

    public function test_actualizar_usuario_admin_funciona(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin(['email' => 'otro-admin@example.com']);
        $this->actingAsApi($admin);

        $this->patchJson("/api/v1/users/{$otherAdmin->id}", [
            'name' => 'No permitido',
        ])->assertOk()
            ->assertJsonPath('data.name', 'No permitido');
    }

    public function test_admin_puede_eliminar_y_restaurar_usuario(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createModerator();
        $this->actingAsApi($admin);

        $this->deleteJson("/api/v1/users/{$target->id}")
            ->assertOk();

        $this->assertSoftDeleted($target);

        $this->putJson("/api/v1/users/{$target->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.deleted_at', null);
    }

    public function test_se_puede_eliminar_usuario_admin(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin();
        $this->actingAsApi($admin);

        $this->deleteJson("/api/v1/users/{$otherAdmin->id}")
            ->assertOk();
    }

    public function test_admin_puede_activar_y_desactivar_usuario(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createModerator(['is_active' => true]);
        $this->actingAsApi($admin);

        $this->deleteJson("/api/v1/users/{$target->id}/activation")
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $this->putJson("/api/v1/users/{$target->id}/activation")
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
    }

    public function test_admin_puede_consultar_y_sincronizar_roles(): void
    {
        $admin = $this->createAdmin();
        $target = $this->createModerator();
        $this->actingAsApi($admin);

        $this->getJson("/api/v1/users/{$target->id}/roles")
            ->assertOk()
            ->assertJsonFragment(['moderator']);

        $this->putJson("/api/v1/users/{$target->id}/roles", [
            'roles' => ['moderator'],
        ])->assertOk()
            ->assertJsonFragment(['moderator']);
    }

    public function test_sincronizar_roles_valida_entrada_y_permite_admins(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin();
        $target = $this->createModerator();
        $this->actingAsApi($admin);

        $this->putJson("/api/v1/users/{$target->id}/roles", [
            'roles' => [],
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['roles']);

        $this->putJson("/api/v1/users/{$otherAdmin->id}/roles", [
            'roles' => ['moderator'],
        ])->assertOk()
            ->assertJsonFragment(['moderator']);
    }
}
