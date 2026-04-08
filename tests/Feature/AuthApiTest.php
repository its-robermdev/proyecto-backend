<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    public function test_login_exitoso_devuelve_token_y_usuario(): void
    {
        $user = User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('message', 'Login successful.')
            ->assertJsonPath('data.user.email', 'admin@example.com')
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email'],
                ],
            ]);

        $this->assertDatabaseCount((new PersonalAccessToken())->getTable(), 1);
    }

    public function test_login_falla_con_password_incorrecto(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'admin@example.com',
            'password' => 'incorrecta',
        ])->assertStatus(422)
            ->assertJsonPath('message', 'Email or password incorrect.');
    }

    public function test_login_falla_si_usuario_esta_inactivo(): void
    {
        User::factory()->inactive()->create([
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'inactive@example.com',
            'password' => 'password',
        ])->assertForbidden()
            ->assertJsonPath('message', 'This user account is inactive.');
    }

    public function test_logout_elimina_tokens_del_usuario(): void
    {
        $user = User::factory()->create();
        $user->createToken('web');
        $user->createToken('mobile');

        $this->actingAsApi($user);

        $this->postJson('/api/v1/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logout successful.');

        $this->assertDatabaseCount((new PersonalAccessToken())->getTable(), 0);
    }

    public function test_profile_devuelve_datos_del_usuario_autenticado(): void
    {
        $user = User::factory()->create(['email' => 'perfil@example.com']);
        $this->actingAsApi($user);

        $this->getJson('/api/v1/auth/profile')
            ->assertOk()
            ->assertJsonPath('data.email', 'perfil@example.com')
            ->assertJsonPath('message', 'Profile retrieved successfully.');
    }

    public function test_logout_y_profile_requieren_autenticacion(): void
    {
        $this->postJson('/api/v1/auth/logout')->assertUnauthorized();
        $this->getJson('/api/v1/auth/profile')->assertUnauthorized();
    }
}
