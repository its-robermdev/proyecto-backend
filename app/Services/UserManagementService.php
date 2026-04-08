<?php

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
    // Crea usuario y aplica roles iniciales en una transaccion.
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
    // Actualiza el perfil propio sin tocar roles ni activacion.
    public function updateProfile(User $user, array $validated): User
    {
        $user->update($validated);

        return $user->refresh();
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    // Actualiza atributos administrativos de usuarios gestionables.
    public function updateManagedUser(User $user, array $validated): User
    {
        $this->ensureTargetIsNotAdmin($user, 'Cannot update admin accounts.');
        $user->update($validated);

        return $user->refresh();
    }

    // Soft delete bloqueado sobre cuentas admin.
    public function softDelete(User $user): void
    {
        $this->ensureTargetIsNotAdmin($user, 'Cannot delete admin accounts.');
        $user->delete();
    }

    // Restaura usuario eliminado logicamente si no es admin.
    public function restore(int $userId): User
    {
        $user = User::withTrashed()->findOrFail($userId);
        $this->ensureTargetIsNotAdmin($user, 'Cannot restore admin accounts.');
        $user->restore();

        return $user->refresh();
    }

    // Habilita acceso del usuario si no es admin.
    public function activate(User $user): User
    {
        $this->ensureTargetIsNotAdmin($user, 'Cannot activate admin accounts through this endpoint.');
        $user->update(['is_active' => true]);

        return $user->refresh();
    }

    // Deshabilita acceso del usuario si no es admin.
    public function deactivate(User $user): User
    {
        $this->ensureTargetIsNotAdmin($user, 'Cannot deactivate admin accounts.');
        $user->update(['is_active' => false]);

        return $user->refresh();
    }

    /**
     * @param  array<int, string>  $roles
     */
    // Reemplaza por completo los roles del usuario si no es admin.
    public function syncRoles(User $user, array $roles): User
    {
        $this->ensureTargetIsNotAdmin($user, 'Cannot synchronize roles for admin accounts.');

        if ($roles === []) {
            throw ValidationException::withMessages([
                'roles' => 'At least one role is required.',
            ]);
        }

        $user->syncRoles($roles);

        return $user->load('roles');
    }

    private function ensureTargetIsNotAdmin(User $user, string $message): void
    {
        if (! $user->hasRole('admin')) {
            return;
        }

        throw ValidationException::withMessages([
            'user' => $message,
        ]);
    }
}
