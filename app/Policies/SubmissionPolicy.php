<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;
use Database\Seeders\PermissionCatalog;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionCatalog::ALL['view_event_submissions']);
    }

    public function view(User $user, Submission $submission): bool
    {
        if (! $user->hasPermissionTo(PermissionCatalog::ALL['view_event_submissions'])) {
            return false;
        }

        if ($user->hasPermissionTo(PermissionCatalog::ALL['view_any_event'])) {
            return true;
        }

        $submission->loadMissing('event');

        return $submission->event !== null
            && $submission->event->moderators()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Submission $submission): bool
    {
        return $user->hasPermissionTo(PermissionCatalog::ALL['update_submission']);
    }

    public function review(User $user, Submission $submission): bool
    {
        if (! $user->hasPermissionTo(PermissionCatalog::ALL['evaluate_submission'])) {
            return false;
        }

        if ($user->hasPermissionTo(PermissionCatalog::ALL['view_any_event'])) {
            return true;
        }

        $submission->loadMissing('event');

        return $submission->event !== null
            && $submission->event->moderators()->where('users.id', $user->id)->exists();
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $user->hasPermissionTo(PermissionCatalog::ALL['delete_submission']);
    }

    public function restore(User $user, Submission $submission): bool
    {
        return false;
    }

    public function forceDelete(User $user, Submission $submission): bool
    {
        return false;
    }
}
