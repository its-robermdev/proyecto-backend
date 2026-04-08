<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isReviewer($user);
    }

    public function view(User $user, Submission $submission): bool
    {
        return $this->isReviewer($user);
    }

    public function create(User $user): bool
    {
        return $this->isReviewer($user);
    }

    public function update(User $user, Submission $submission): bool
    {
        return $this->isReviewer($user);
    }

    public function review(User $user, Submission $submission): bool
    {
        return $this->isReviewer($user);
    }

    public function delete(User $user, Submission $submission): bool
    {
        return false;
    }

    public function restore(User $user, Submission $submission): bool
    {
        return false;
    }

    public function forceDelete(User $user, Submission $submission): bool
    {
        return false;
    }

    private function isReviewer(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'moderator']);
    }
}
