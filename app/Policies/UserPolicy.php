<?php

namespace App\Policies;

use App\Models\User;
use App\Support\PermissionCatalog;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles']);
    }

    public function view(User $user, User $model): bool
    {
        if ($model->is_root === true) {
            return $user->is_root === true;
        }

        if ($user->is($model) && $user->hasPermissionTo(PermissionCatalog::ALL['edit_own_profile'])) {
            return true;
        }

        if ($model->hasRole('admin')) {
            return $user->is_root === true;
        }

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

        if ($actor->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles'])) {
            return ! $target->hasRole('admin') || $actor->is_root === true;
        }

        return false;
    }

    public function delete(User $actor, User $target): bool
    {
        if ($this->isRootManagingSelf($actor, $target)) {
            return false;
        }

        return $actor->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles'])
            && (! $target->hasRole('admin') || $actor->is_root === true);
    }

    public function restore(User $actor, User $target): bool
    {
        if ($this->isRootManagingSelf($actor, $target)) {
            return false;
        }

        return $actor->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles'])
            && (! $target->hasRole('admin') || $actor->is_root === true);
    }

    public function roles(User $actor, User $target): bool
    {
        return $this->view($actor, $target);
    }

    public function syncRoles(User $actor, User $target): bool
    {
        return $actor->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles'])
            && (! $target->hasRole('admin') || $actor->is_root === true);
    }

    public function activate(User $actor, User $target): bool
    {
        if ($this->isRootManagingSelf($actor, $target)) {
            return false;
        }

        return $actor->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles'])
            && (! $target->hasRole('admin') || $actor->is_root === true);
    }

    public function deactivate(User $actor, User $target): bool
    {
        if ($this->isRootManagingSelf($actor, $target)) {
            return false;
        }

        return $actor->hasPermissionTo(PermissionCatalog::ALL['manage_moderator_profiles'])
            && (! $target->hasRole('admin') || $actor->is_root === true);
    }

    private function isRootManagingSelf(User $actor, User $target): bool
    {
        return $actor->is_root === true && $actor->is($target);
    }
}
