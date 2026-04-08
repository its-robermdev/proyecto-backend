<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Event;
use App\Models\User;

class EventPolicy
{
    // Permite listar público sin autenticación y panel interno para roles válidos.
    public function viewAny(?User $user): bool
    {
        if (! $user instanceof User) {
            return true;
        }

        return $user->hasAnyRole(['admin', 'moderator']);
    }

    // Permite ver publicados públicamente; no publicados solo a admin/mod asignado.
    public function view(?User $user, Event $event): bool
    {
        if ($event->status === 'published') {
            return true;
        }

        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasRole('admin')) {
            return true;
        }

        if (! $user->hasRole('moderator')) {
            return false;
        }

        return $event->moderators()->where('users.id', $user->id)->exists();
    }

    // Solo admin puede crear eventos.
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    // Solo admin puede editar eventos.
    public function update(User $user, Event $event): bool
    {
        return $user->hasRole('admin');
    }

    // Solo admin puede eliminar eventos.
    public function delete(User $user, Event $event): bool
    {
        return $user->hasRole('admin');
    }

    // Solo admin puede restaurar eventos.
    public function restore(User $user, Event $event): bool
    {
        return $user->hasRole('admin');
    }

    // Borrado físico deshabilitado por política.
    public function forceDelete(User $user, Event $event): bool
    {
        return false;
    }

    // Solo admin puede ejecutar transición de estado.
    public function updateStatus(User $user, Event $event): bool
    {
        return $user->hasRole('admin');
    }

    // Solo admin configura schema y activación del formulario.
    public function updateForm(User $user, Event $event): bool
    {
        return $user->hasRole('admin');
    }

    // Admin y moderador autorizado pueden consultar responsables del evento.
    public function inspectModerators(User $user, Event $event): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if (! $user->hasRole('moderator')) {
            return false;
        }

        return $event->status === 'published'
            || $event->moderators()->where('users.id', $user->id)->exists();
    }
}
