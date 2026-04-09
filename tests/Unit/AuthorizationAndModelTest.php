<?php

namespace Tests\Unit;

use App\Models\Submission;
use App\Policies\EventPolicy;
use App\Policies\SubmissionPolicy;
use App\Policies\UserPolicy;
use Tests\TestCase;

class AuthorizationAndModelTest extends TestCase
{
    public function test_event_policy_permite_evento_publicado_a_cualquiera(): void
    {
        $event = $this->createPublishedEvent();

        $this->assertTrue(app(EventPolicy::class)->view(null, $event));
    }

    public function test_event_policy_permite_moderador_asignado_en_evento_no_publicado(): void
    {
        $moderator = $this->createModerator();
        $event = $this->createDraftEvent();
        $this->assignModerator($event, $moderator);

        $this->assertTrue(app(EventPolicy::class)->view($moderator, $event));
    }

    public function test_submission_policy_permite_ver_y_revisar_a_moderador_asignado(): void
    {
        $moderator = $this->createModerator();
        $event = $this->createPublishedEvent();
        $submission = $this->createSubmission($event);
        $this->assignModerator($event, $moderator);
        $policy = app(SubmissionPolicy::class);

        $this->assertTrue($policy->view($moderator, $submission));
        $this->assertTrue($policy->review($moderator, $submission));
    }

    public function test_submission_policy_niega_usuario_no_asignado(): void
    {
        $user = $this->createModerator();
        $event = $this->createPublishedEvent();
        $submission = $this->createSubmission($event);

        $this->assertFalse(app(SubmissionPolicy::class)->view($user, $submission));
    }

    public function test_user_policy_permite_edicion_propia_a_moderador(): void
    {
        $moderator = $this->createModerator();

        $this->assertTrue(app(UserPolicy::class)->update($moderator, $moderator));
    }

    public function test_user_policy_permite_operaciones_sobre_admin_objetivo(): void
    {
        $admin = $this->createAdmin();
        $otherAdmin = $this->createAdmin();
        $policy = app(UserPolicy::class);

        $this->assertTrue($policy->delete($admin, $otherAdmin));
        $this->assertTrue($policy->syncRoles($admin, $otherAdmin));
    }

    public function test_event_available_spots_count_descuenta_solo_pending_y_approved(): void
    {
        $event = $this->createPublishedEvent(['capacity' => 3]);
        $this->createSubmission($event, ['status' => Submission::STATUS_PENDING]);
        $this->createSubmission($event, ['status' => Submission::STATUS_APPROVED]);
        $this->createSubmission($event, ['status' => Submission::STATUS_REJECTED]);

        $this->assertSame(1, $event->fresh()->availableSpotsCount());
        $this->assertTrue($event->fresh()->hasAvailableSpots());
    }

    public function test_submission_occupying_statuses_son_pending_y_approved(): void
    {
        $this->assertSame(
            [Submission::STATUS_PENDING, Submission::STATUS_APPROVED],
            Submission::occupyingStatuses(),
        );
    }
}
