<?php

namespace App\Policies;

use App\Models\Event;
use App\Models\User;
use App\Support\PermissionCatalog;

class EventPolicy
{
    // Public listing stays available; authenticated access depends on permissions.
    public function viewAny(?User $user): bool
    {
        if (! $user instanceof User) {
            return true;
        }

        return $user->hasPermissionTo(PermissionCatalog::ALL['view_any_event'])
            || $user->hasPermissionTo(PermissionCatalog::ALL['view_own_event']);
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

        if ($user->hasPermissionTo(PermissionCatalog::ALL['view_any_event'])) {
            return true;
        }

        if (! $user->hasPermissionTo(PermissionCatalog::ALL['view_own_event'])) {
            return false;
        }

        return $this->isAssignedModerator($user, $event);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionCatalog::ALL['create_event']);
    }

    public function update(User $user, Event $event): bool
    {
        return $user->hasPermissionTo(PermissionCatalog::ALL['update_event']);
    }

    public function delete(User $user, Event $event): bool
    {
        return $user->hasPermissionTo(PermissionCatalog::ALL['delete_event']);
    }

    public function restore(User $user, Event $event): bool
    {
        return $user->hasPermissionTo(PermissionCatalog::ALL['delete_event']);
    }

    public function forceDelete(User $user, Event $event): bool
    {
        return false;
    }

    public function updateStatus(User $user, Event $event): bool
    {
        return $user->hasPermissionTo(PermissionCatalog::ALL['update_event']);
    }

    public function updateForm(User $user, Event $event): bool
    {
        return $user->hasPermissionTo(PermissionCatalog::ALL['update_event']);
    }

    // Moderator listing mirrors event visibility.
    public function inspectModerators(User $user, Event $event): bool
    {
        if ($event->status === 'published') {
            return true;
        }

        return $user->hasPermissionTo(PermissionCatalog::ALL['view_any_event'])
            || ($user->hasPermissionTo(PermissionCatalog::ALL['view_own_event']) && $this->isAssignedModerator($user, $event));
    }

    public function assignModerators(User $user, Event $event): bool
    {
        return $user->hasPermissionTo(PermissionCatalog::ALL['assign_event_moderators']);
    }

    private function isAssignedModerator(User $user, Event $event): bool
    {
        return $event->moderators()
            ->where('users.id', $user->id)
            ->exists();
    }
}
