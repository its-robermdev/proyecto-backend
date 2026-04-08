<?php

namespace App\Policies;

use App\Models\User;
use Database\Seeders\PermissionName;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::MANAGE_MODERATOR_PROFILES->value);
    }

    public function view(User $user, User $model): bool
    {
        return $user->hasPermissionTo(PermissionName::MANAGE_MODERATOR_PROFILES->value);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::MANAGE_MODERATOR_PROFILES->value);
    }

    public function update(User $actor, User $target): bool
    {
        if ($actor->is($target) && $actor->hasPermissionTo(PermissionName::EDIT_OWN_PROFILE->value)) {
            return true;
        }

        if ($target->hasRole('admin')) {
            return false;
        }

        if ($actor->hasPermissionTo(PermissionName::MANAGE_MODERATOR_PROFILES->value)) {
            return true;
        }

        return false;
    }

    public function delete(User $actor, User $target): bool
    {
        if ($target->hasRole('admin')) {
            return false;
        }

        return $actor->hasPermissionTo(PermissionName::MANAGE_MODERATOR_PROFILES->value);
    }

    public function restore(User $actor, User $target): bool
    {
        if ($target->hasRole('admin')) {
            return false;
        }

        return $actor->hasPermissionTo(PermissionName::MANAGE_MODERATOR_PROFILES->value);
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

        return $actor->hasPermissionTo(PermissionName::MANAGE_MODERATOR_PROFILES->value);
    }

    public function activate(User $actor, User $target): bool
    {
        if ($target->hasRole('admin')) {
            return false;
        }

        return $actor->hasPermissionTo(PermissionName::MANAGE_MODERATOR_PROFILES->value);
    }

    public function deactivate(User $actor, User $target): bool
    {
        if ($target->hasRole('admin')) {
            return false;
        }

        return $actor->hasPermissionTo(PermissionName::MANAGE_MODERATOR_PROFILES->value);
    }
}
