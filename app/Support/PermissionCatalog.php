<?php

namespace App\Support;

final class PermissionCatalog
{
    public const GUARD_NAME = 'api';

    public const ALL = [
        'view_any_event' => 'view_any_event',
        'view_own_event' => 'view_own_event',
        'create_event' => 'create_event',
        'update_event' => 'update_event',
        'delete_event' => 'delete_event',
        'manage_moderator_profiles' => 'manage_moderator_profiles',
        'edit_own_profile' => 'edit_own_profile',
        'assign_event_moderators' => 'assign_event_moderators',
        'view_event_submissions' => 'view_event_submissions',
        'evaluate_submission' => 'evaluate_submission',
        'update_submission' => 'update_submission',
        'delete_submission' => 'delete_submission',
    ];

    public const BY_ROLE = [
        'admin' => [
            'view_any_event',
            'create_event',
            'update_event',
            'delete_event',
            'manage_moderator_profiles',
            'edit_own_profile',
            'assign_event_moderators',
            'view_event_submissions',
            'evaluate_submission',
            'update_submission',
            'delete_submission',
        ],
        'moderator' => [
            'view_own_event',
            'view_event_submissions',
            'evaluate_submission',
            'edit_own_profile',
        ],
    ];
}
