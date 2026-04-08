<?php

namespace Tests\Concerns;

use App\Models\Event;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

trait BuildsApiData
{
    protected function actingAsApi(User $user): User
    {
        Sanctum::actingAs($user);

        return $user;
    }

    protected function createAdmin(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('admin');

        return $user;
    }

    protected function createModerator(array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->assignRole('moderator');

        return $user;
    }

    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function validFormSchema(): array
    {
        return [
            [
                'step_name' => 'Perfil',
                'fields' => [
                    [
                        'name' => 'portfolio_url',
                        'type' => 'url',
                        'label' => 'Portafolio',
                        'validation' => 'required|url',
                    ],
                    [
                        'name' => 'experience_level',
                        'type' => 'select',
                        'label' => 'Experiencia',
                        'options' => ['junior', 'mid', 'senior'],
                        'validation' => 'required|in:junior,mid,senior',
                    ],
                ],
            ],
            [
                'step_name' => 'Motivacion',
                'fields' => [
                    [
                        'name' => 'motivation',
                        'type' => 'textarea',
                        'label' => 'Motivacion',
                        'validation' => 'required|string|min:10',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validFormAnswers(array $overrides = []): array
    {
        return array_merge([
            'portfolio_url' => 'https://example.com/portfolio',
            'experience_level' => 'mid',
            'motivation' => 'Quiero participar y aprender mucho.',
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    protected function eventPayload(array $overrides = []): array
    {
        $title = $overrides['title'] ?? 'Evento '.Str::random(8);

        return array_merge([
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'type' => 'hackathon',
            'modality' => 'hybrid',
            'description' => 'Descripcion extensa del evento para pruebas.',
            'start_date' => now()->addDays(15)->toISOString(),
            'end_date' => now()->addDays(16)->toISOString(),
            'registration_deadline' => now()->addDays(10)->toISOString(),
            'capacity' => 50,
            'requires_approval' => true,
            'allows_teams' => true,
        ], $overrides);
    }

    protected function createEvent(array $attributes = [], ?User $creator = null): Event
    {
        $creator ??= $this->createAdmin();

        return Event::factory()->create(array_merge([
            'created_by' => $creator->id,
            'form_schema' => $this->validFormSchema(),
        ], $attributes));
    }

    protected function createDraftEvent(array $attributes = [], ?User $creator = null): Event
    {
        return $this->createEvent(array_merge([
            'status' => 'draft',
            'form_is_active' => false,
        ], $attributes), $creator);
    }

    protected function createPublishedEvent(array $attributes = [], ?User $creator = null): Event
    {
        return $this->createEvent(array_merge([
            'status' => 'published',
            'form_is_active' => true,
            'registration_deadline' => now()->addDays(5),
        ], $attributes), $creator);
    }

    protected function assignModerator(Event $event, ?User $moderator = null): User
    {
        $moderator ??= $this->createModerator();
        $event->moderators()->syncWithoutDetaching([$moderator->id]);

        return $moderator;
    }

    /**
     * @return array<string, mixed>
     */
    protected function individualSubmissionPayload(array $overrides = []): array
    {
        return array_merge([
            'submitted_by_email' => 'postulante'.Str::lower(Str::random(6)).'@example.com',
            'submitted_by_name' => 'Postulante Demo',
            'participation_type' => 'individual',
            'form_answers' => $this->validFormAnswers(),
        ], $overrides);
    }

    /**
     * @return array<string, mixed>
     */
    protected function teamSubmissionPayload(array $overrides = []): array
    {
        return array_merge([
            'submitted_by_email' => 'equipo'.Str::lower(Str::random(6)).'@example.com',
            'submitted_by_name' => 'Equipo Demo',
            'participation_type' => 'team',
            'team_name' => 'Los Builders',
            'members' => [
                [
                    'full_name' => 'Capitana Uno',
                    'email' => 'captain'.Str::lower(Str::random(5)).'@example.com',
                    'is_captain' => true,
                ],
                [
                    'full_name' => 'Miembro Dos',
                    'email' => 'member'.Str::lower(Str::random(5)).'@example.com',
                    'is_captain' => false,
                ],
            ],
            'form_answers' => $this->validFormAnswers(),
        ], $overrides);
    }

    protected function createSubmission(Event $event, array $attributes = []): Submission
    {
        return Submission::factory()->for($event)->create(array_merge([
            'status' => Submission::STATUS_PENDING,
            'participation_type' => 'individual',
            'form_answers' => $this->validFormAnswers(),
        ], $attributes));
    }
}
