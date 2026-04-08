<?php

namespace App\Policies;

use App\Models\User;
use Database\Seeders\PermissionCatalog;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles']);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles']);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles']);
    }

    public function update(User $actor, User $target): bool
    {
        if ($actor->is($target) && $actor->hasPermissionTo(PermissionCatalog::ALL['edit_own_profile'])) {
            return true;
        }

        if ($target->hasRole('admin')) {
            return false;
        }

        if ($actor->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles'])) {
            return true;
        }

        return false;
    }

    public function delete(User $actor, User $target): bool
    {
        if ($target->hasRole('admin')) {
            return false;
        }

        return $actor->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles']);
    }

    public function restore(User $actor, User $target): bool
    {
        if ($target->hasRole('admin')) {
            return false;
        }

        return $actor->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles']);
    }

    public function roles(User $actor, User $target): bool
    {
        return $this->view($actor, $target);
    }

    public function syncRoles(User $actor, User $target): bool
    {
        if ($target->hasRole('admin')) {
            return false;
        }

        return $actor->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles']);
    }

    public function activate(User $actor, User $target): bool
    {
        if ($target->hasRole('admin')) {
            return false;
        }

        return $actor->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles']);
    }

    public function deactivate(User $actor, User $target): bool
    {
        if ($target->hasRole('admin')) {
            return false;
        }

        return $actor->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles']);
    }
}
