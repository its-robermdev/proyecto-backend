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
    public function updateManagedUser(User $actor, User $user, array $validated): User
    {
        $this->ensureActorCanManageTarget($actor, $user, 'Cannot update admin accounts.');
        $user->update($validated);

        return $user->refresh();
    }

    // Soft delete bloqueado sobre cuentas admin.
    public function softDelete(User $actor, User $user): void
    {
        $this->ensureRootIsNotManagingSelf($actor, $user, 'Root admin cannot delete their own account.');
        $this->ensureActorCanManageTarget($actor, $user, 'Cannot delete admin accounts.');
        $user->delete();
    }

    // Restaura usuario eliminado logicamente si no es admin.
    public function restore(User $actor, int $userId): User
    {
        $user = User::withTrashed()->findOrFail($userId);
        $this->ensureRootIsNotManagingSelf($actor, $user, 'Root admin cannot restore their own account.');
        $this->ensureActorCanManageTarget($actor, $user, 'Cannot restore admin accounts.');
        $user->restore();

        return $user->refresh();
    }

    // Habilita acceso del usuario si no es admin.
    public function activate(User $actor, User $user): User
    {
        $this->ensureRootIsNotManagingSelf($actor, $user, 'Root admin cannot activate their own account.');
        $this->ensureActorCanManageTarget($actor, $user, 'Cannot activate admin accounts through this endpoint.');
        $user->update(['is_active' => true]);

        return $user->refresh();
    }

    // Deshabilita acceso del usuario si no es admin.
    public function deactivate(User $actor, User $user): User
    {
        $this->ensureRootIsNotManagingSelf($actor, $user, 'Root admin cannot deactivate their own account.');
        $this->ensureActorCanManageTarget($actor, $user, 'Cannot deactivate admin accounts.');
        $user->update(['is_active' => false]);

        return $user->refresh();
    }

    /**
     * @param  array<int, string>  $roles
     */
    // Reemplaza por completo los roles del usuario si no es admin.
    public function syncRoles(User $actor, User $user, array $roles): User
    {
        $this->ensureActorCanManageTarget($actor, $user, 'Cannot synchronize roles for admin accounts.');

        if ($roles === []) {
            throw ValidationException::withMessages([
                'roles' => 'At least one role is required.',
            ]);
        }

        $user->syncRoles($roles);

        return $user->load('roles');
    }

    private function ensureActorCanManageTarget(User $actor, User $user, string $message): void
    {
        if (! $user->hasRole('admin') || $actor->is_root === true) {
            return;
        }

        throw ValidationException::withMessages([
            'user' => $message,
        ]);
    }

    private function ensureRootIsNotManagingSelf(User $actor, User $user, string $message): void
    {
        if (! ($actor->is_root === true && $actor->is($user))) {
            return;
        }

        throw ValidationException::withMessages([
            'user' => $message,
        ]);
    }
}
