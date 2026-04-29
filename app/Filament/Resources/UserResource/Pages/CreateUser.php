<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        $data = $this->form->getRawState();
        $record = $this->record;

        if (! empty($data['role'])) {
            $record->syncRoles([$data['role']]);
        }

        if (($data['role'] ?? null) === 'staff') {
            $this->syncStaffPermissions($record, $data);
        }
    }

    private function syncStaffPermissions(\App\Models\User $user, array $data): void
    {
        $perms = [];
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'perm_') && $value === true) {
                $perms[] = substr($key, 5);
            }
        }
        $user->syncPermissions($perms);
    }
}
