<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasPricebookPermissions
{
    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->hasPermissionTo('view_' . static::$permissionPrefix);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->hasPermissionTo('create_' . static::$permissionPrefix);
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }
        if ($user->hasRole(['super_admin', 'admin'])) {
            return true;
        }

        return $user->hasPermissionTo('edit_' . static::$permissionPrefix);
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public static function canDeleteAny(): bool
    {
        return auth()->user()?->hasRole(['super_admin', 'admin']) ?? false;
    }
}
