<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Submission;
use App\Rules\EventHasAvailableSpots;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubmitFormService
{
    /**
     * @param  array<string, mixed>  $validated
     */
    // Crea submission y miembros asociados en una sola transaccion atomica.
    public function submit(Event $event, array $validated): Submission
    {
        return DB::transaction(function () use ($event, $validated): Submission {
            $event = $event->fresh();

            if (! $event instanceof Event || ! $event->hasAvailableSpots()) {
                throw ValidationException::withMessages([
                    'event_capacity_guard' => EventHasAvailableSpots::MESSAGE,
                ]);
            }

            $participationType = (string) $validated['participation_type'];

            // La inscripcion y sus miembros forman una sola unidad de negocio:
            // si falla un miembro, no debe quedar una submission huerfana o parcial.
            $submission = $event->submissions()->create([
                'submitted_by_email' => $validated['submitted_by_email'],
                'submitted_by_name' => $validated['submitted_by_name'],
                'participation_type' => $participationType,
                'team_name' => $participationType === 'team' ? ($validated['team_name'] ?? null) : null,
                'status' => Submission::STATUS_PENDING,
                'form_answers' => $validated['form_answers'] ?? [],
            ]);

            if ($participationType === 'team') {
                $members = collect($validated['members'] ?? [])
                    ->map(fn (array $member): array => Arr::only($member, ['full_name', 'email', 'is_captain']))
                    ->all();

                $submission->members()->createMany($members);
            }

            return $submission->load(['event', 'members', 'reviewer']);
        });
    }
}
