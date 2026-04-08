<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Event;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;

class EventModeratorService
{
    // Asigna moderador verificando existencia y rol compatible.
    public function assign(Event $event, int $userId): User
    {
        $moderator = User::query()->find($userId);

        if (! $moderator instanceof User) {
            throw (new ModelNotFoundException)->setModel(User::class, [$userId]);
        }

        if (! $moderator->hasRole('moderator')) {
            throw ValidationException::withMessages([
                'user_id' => 'The selected user does not have the moderator role.',
            ]);
        }

        $event->moderators()->syncWithoutDetaching([$moderator->id]);

        return $moderator;
    }

    // Elimina la asignación evento-moderador.
    public function remove(Event $event, User $moderator): void
    {
        $event->moderators()->detach($moderator->id);
    }
}
