<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    // Listado permitido solo a perfiles revisores.
    public function viewAny(User $user): bool
    {
        return $this->isReviewer($user);
    }

    // Lectura de submissions permitida a revisores.
    public function view(User $user, Submission $submission): bool
    {
        return $this->isReviewer($user);
    }

    // create no se usa en ruta autenticada, se conserva por consistencia de policy.
    public function create(User $user): bool
    {
        return $this->isReviewer($user);
    }

    // update general restringido a revisores.
    public function update(User $user, Submission $submission): bool
    {
        return $this->isReviewer($user);
    }

    // review explícito para endpoint de aprobación/rechazo.
    public function review(User $user, Submission $submission): bool
    {
        return $this->isReviewer($user);
    }

    // Borrado lógico de submissions no permitido por ahora.
    public function delete(User $user, Submission $submission): bool
    {
        return false;
    }

    // Restauración deshabilitada por ahora.
    public function restore(User $user, Submission $submission): bool
    {
        return false;
    }

    // Borrado físico deshabilitado.
    public function forceDelete(User $user, Submission $submission): bool
    {
        return false;
    }

    // Revisor válido: admin o moderator.
    private function isReviewer(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'moderator']);
    }
}
