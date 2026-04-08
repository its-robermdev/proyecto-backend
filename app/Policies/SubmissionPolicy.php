<?php

namespace App\Policies;

use App\Models\Submission;
use App\Models\User;
use Database\Seeders\PermissionName;

class SubmissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo(PermissionName::VIEW_EVENT_SUBMISSIONS->value);
    }

    public function view(User $user, Submission $submission): bool
    {
        if (! $user->hasPermissionTo(PermissionName::VIEW_EVENT_SUBMISSIONS->value)) {
            return false;
        }

        if ($user->hasPermissionTo(PermissionName::VIEW_ANY_EVENT->value)) {
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
        return $user->hasPermissionTo(PermissionName::UPDATE_SUBMISSION->value);
    }

    public function review(User $user, Submission $submission): bool
    {
        if (! $user->hasPermissionTo(PermissionName::EVALUATE_SUBMISSION->value)) {
            return false;
        }

        if ($user->hasPermissionTo(PermissionName::VIEW_ANY_EVENT->value)) {
            return true;
        }

        $submission->loadMissing('event');

        return $submission->event !== null
            && $submission->event->moderators()->where('users.id', $user->id)->exists();
    }

    public function delete(User $user, Submission $submission): bool
    {
        return $user->hasPermissionTo(PermissionName::DELETE_SUBMISSION->value);
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
