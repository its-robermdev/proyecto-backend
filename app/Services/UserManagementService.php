<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    /**
     * @param  array<string, mixed>  $validated
     */
    // Crea usuario y aplica roles iniciales en una transacción.
    public function create(array $validated): User
    {
        return DB::transaction(function () use ($validated): User {
            $roles = Arr::get($validated, 'roles', []);
            $userData = Arr::except($validated, ['roles']);

            /** @var User $user */
            $user = User::query()->create($userData);

            if (is_array($roles) && $roles !== []) {
                $user->syncRoles($roles);
            }

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    // Actualiza atributos editables de usuario.
    public function update(User $user, array $validated): User
    {
        $user->update($validated);

        return $user->refresh();
    }

    // Soft delete con regla de protección para no eliminar al último admin.
    public function softDelete(User $user): void
    {
        if ($user->hasRole('admin') && User::role('admin')->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'Cannot delete the last admin user.',
            ]);
        }

        $user->delete();
    }

    // Restaura usuario eliminado lógicamente.
    public function restore(int $userId): User
    {
        $user = User::withTrashed()->findOrFail($userId);
        $user->restore();

        return $user->refresh();
    }

    // Habilita acceso del usuario.
    public function activate(User $user): User
    {
        $user->update(['is_active' => true]);

        return $user->refresh();
    }

    // Deshabilita acceso cuidando no dejar el sistema sin admins activos.
    public function deactivate(User $user): User
    {
        if ($user->hasRole('admin') && User::role('admin')->where('users.id', '!=', $user->id)->count() === 0) {
            throw ValidationException::withMessages([
                'user' => 'Cannot deactivate the last admin user.',
            ]);
        }

        $user->update(['is_active' => false]);

        return $user->refresh();
    }

    /**
     * @param  array<int, string>  $roles
     */
    // Reemplaza por completo los roles del usuario.
    public function syncRoles(User $user, array $roles): User
    {
        if ($roles === []) {
            throw ValidationException::withMessages([
                'roles' => 'At least one role is required.',
            ]);
        }

        $user->syncRoles($roles);

        return $user->load('roles');
    }
}
