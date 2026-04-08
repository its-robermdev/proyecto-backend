<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use Database\Seeders\PermissionName;

class EventPolicy
{
    // Public listing stays available; authenticated access depends on permissions.
    public function viewAny(?User $user): bool
    {
        if (! $user instanceof User) {
            return true;
        }

        return $user->hasPermissionTo(PermissionName::VIEW_ANY_EVENT->value)
            || $user->hasPermissionTo(PermissionName::VIEW_OWN_EVENT->value);
    }

    // Published events are public; non-published events require permission-based visibility.
    public function view(?User $user, Event $event): bool
    {
        if ($event->status === 'published') {
            return true;
        }

        if (! $user instanceof User) {
            return false;
        }

        if ($user->hasPermissionTo(PermissionName::VIEW_ANY_EVENT->value)) {
            return true;
        }

        if (! $user->hasPermissionTo(PermissionName::VIEW_OWN_EVENT->value)) {
            return false;
        }

        return $this->isAssignedModerator($user, $event);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::CREATE_EVENT->value);
    }

    public function update(User $user, Event $event): bool
    {
        return $user->hasPermissionTo(PermissionName::UPDATE_EVENT->value);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->hasPermissionTo(PermissionName::DELETE_EVENT->value);
    }

    public function restore(User $user, Event $event): bool
    {
        return $user->hasPermissionTo(PermissionName::DELETE_EVENT->value);
    }

    public function forceDelete(User $user, Event $event): bool
    {
        return false;
    }

    public function updateStatus(User $user, Event $event): bool
    {
        return $user->hasPermissionTo(PermissionName::UPDATE_EVENT->value);
    }

    public function updateForm(User $user, Event $event): bool
    {
        return $user->hasPermissionTo(PermissionName::UPDATE_EVENT->value);
    }

    // Moderator listing mirrors event visibility.
    public function inspectModerators(User $user, Event $event): bool
    {
        if ($event->status === 'published') {
            return true;
        }

        return $user->hasPermissionTo(PermissionName::VIEW_ANY_EVENT->value)
            || ($user->hasPermissionTo(PermissionName::VIEW_OWN_EVENT->value) && $this->isAssignedModerator($user, $event));
    }

    public function assignModerators(User $user, Event $event): bool
    {
        return $user->hasPermissionTo(PermissionName::ASSIGN_EVENT_MODERATORS->value);
    }

    private function isAssignedModerator(User $user, Event $event): bool
    {
        return $event->moderators()
            ->where('users.id', $user->id)
            ->exists();
    }
}
