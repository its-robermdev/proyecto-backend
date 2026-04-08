<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

enum PermissionName: string
{
    case VIEW_ANY_EVENT = 'view_any_event';
    case VIEW_OWN_EVENT = 'view_own_event';
    case CREATE_EVENT = 'create_event';
    case UPDATE_EVENT = 'update_event';
    case DELETE_EVENT = 'delete_event';
    case MANAGE_MODERATOR_PROFILES = 'manage_moderator_profiles';
    case EDIT_OWN_PROFILE = 'edit_own_profile';
    case ASSIGN_EVENT_MODERATORS = 'assign_event_moderators';
    case VIEW_EVENT_SUBMISSIONS = 'view_event_submissions';
    case EVALUATE_SUBMISSION = 'evaluate_submission';
    case UPDATE_SUBMISSION = 'update_submission';
    case DELETE_SUBMISSION = 'delete_submission';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return array_map(
            static fn (self $permission): string => $permission->value,
            self::cases(),
        );
    }

    /**
     * @return array<int, string>
     */
    public static function admin(): array
    {
        return array_values(array_diff(self::all(), [self::VIEW_OWN_EVENT->value]));
    }

    /**
     * @return array<int, string>
     */
    public static function moderator(): array
    {
        return [
            self::VIEW_OWN_EVENT->value,
            self::VIEW_EVENT_SUBMISSIONS->value,
            self::EVALUATE_SUBMISSION->value,
            self::EDIT_OWN_PROFILE->value,
        ];
    }
}

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        foreach (PermissionName::all() as $permission) {
            Permission::findOrCreate($permission, 'api');
        }
    }
}
