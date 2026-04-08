<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use Illuminate\Validation\ValidationException;

class EventLifecycleService
{
    /**
     * @var array<string, array<int, string>>
     */
    private array $allowedTransitions = [
        'draft' => ['published', 'cancelled', 'archived'],
        'published' => ['closed', 'cancelled', 'archived'],
        'closed' => ['archived'],
        'cancelled' => ['archived'],
        'archived' => [],
    ];

    // Aplica transición de estado validando el diagrama permitido.
    public function transitionStatus(Event $event, string $targetStatus): Event
    {
        $currentStatus = (string) $event->status;

        if ($currentStatus === $targetStatus) {
            return $event;
        }

        $allowedTargets = $this->allowedTransitions[$currentStatus] ?? [];

        if (! in_array($targetStatus, $allowedTargets, true)) {
            throw ValidationException::withMessages([
                'status' => sprintf('Invalid transition from "%s" to "%s".', $currentStatus, $targetStatus),
            ]);
        }

        if ($targetStatus === 'published') {
            $this->assertPublishCompleteness($event);
        }

        $event->update(['status' => $targetStatus]);

        return $event->refresh();
    }

    // Verifica campos mínimos antes de permitir publicación.
    private function assertPublishCompleteness(Event $event): void
    {
        $errors = [];

        if ($event->title === null || $event->title === '') {
            $errors['title'][] = 'Title is required to publish.';
        }

        if ($event->description === null || $event->description === '') {
            $errors['description'][] = 'Description is required to publish.';
        }

        if ($event->registration_deadline === null) {
            $errors['registration_deadline'][] = 'Registration deadline is required to publish.';
        }

        if ($event->start_date === null) {
            $errors['start_date'][] = 'Start date is required to publish.';
        }

        if ($event->capacity === null || (int) $event->capacity < 1) {
            $errors['capacity'][] = 'Capacity must be greater than zero to publish.';
        }

        if ($event->registration_deadline !== null && $event->start_date !== null && $event->registration_deadline->gt($event->start_date)) {
            $errors['registration_deadline'][] = 'Registration deadline must be before or equal to start date.';
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }
}
