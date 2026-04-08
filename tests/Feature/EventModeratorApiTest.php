<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class EventModeratorApiTest extends TestCase
{
    public function test_listar_moderadores_de_evento_publicado(): void
    {
        $admin = $this->createAdmin();
        $moderator = $this->createModerator(['email' => 'moderator@example.com']);
        $event = $this->createPublishedEvent();
        $this->assignModerator($event, $moderator);
        $this->actingAsApi($admin);

        $this->getJson("/api/v1/events/{$event->id}/moderators")
            ->assertOk()
            ->assertJsonPath('data.moderators.0.email', 'moderator@example.com');
    }

    public function test_asignar_moderador_exitosamente(): void
    {
        $admin = $this->createAdmin();
        $moderator = $this->createModerator();
        $event = $this->createDraftEvent();
        $this->actingAsApi($admin);

        $this->postJson("/api/v1/events/{$event->id}/moderators", [
            'user_id' => $moderator->id,
        ])->assertCreated()
            ->assertJsonPath('data.moderator.id', $moderator->id);

        $this->assertDatabaseHas('event_moderators', [
            'event_id' => $event->id,
            'user_id' => $moderator->id,
        ]);
    }

    public function test_asignar_moderador_falla_si_usuario_no_tiene_rol_moderator(): void
    {
        $admin = $this->createAdmin();
        $user = $this->createUser();
        $event = $this->createDraftEvent();
        $this->actingAsApi($admin);

        $this->postJson("/api/v1/events/{$event->id}/moderators", [
            'user_id' => $user->id,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id']);
    }

    public function test_remover_moderador_exitosamente(): void
    {
        $admin = $this->createAdmin();
        $moderator = $this->createModerator();
        $event = $this->createDraftEvent();
        $this->assignModerator($event, $moderator);
        $this->actingAsApi($admin);

        $this->deleteJson("/api/v1/events/{$event->id}/moderators/{$moderator->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $moderator->id);

        $this->assertDatabaseMissing('event_moderators', [
            'event_id' => $event->id,
            'user_id' => $moderator->id,
        ]);
    }

    public function test_asignar_y_remover_moderador_falla_sin_permiso(): void
    {
        $user = $this->createUser();
        $moderator = $this->createModerator();
        $event = $this->createDraftEvent();
        $this->actingAsApi($user);

        $this->postJson("/api/v1/events/{$event->id}/moderators", [
            'user_id' => $moderator->id,
        ])->assertForbidden();

        $this->deleteJson("/api/v1/events/{$event->id}/moderators/{$moderator->id}")
            ->assertForbidden();
    }
}
