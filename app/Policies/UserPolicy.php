<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    // Gestión de usuarios reservada al rol admin.
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    // Gestión de usuarios reservada al rol admin.
    public function view(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    // Gestión de usuarios reservada al rol admin.
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    // Gestión de usuarios reservada al rol admin.
    public function update(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    // Gestión de usuarios reservada al rol admin.
    public function delete(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }

    // Gestión de usuarios reservada al rol admin.
    public function restore(User $user, User $model): bool
    {
        return $user->hasRole('admin');
    }
}
