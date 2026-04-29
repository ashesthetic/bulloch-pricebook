<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = $this->record;
        $data['role'] = $user->roles->first()?->name;

        foreach ($user->getDirectPermissions() as $permission) {
            $data['perm_' . $permission->name] = true;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();
        $record = $this->record;

        if (! empty($data['role'])) {
            $record->syncRoles([$data['role']]);
        }

        if (($data['role'] ?? null) === 'staff') {
            $this->syncStaffPermissions($record, $data);
        } else {
            // Clear individual permissions when switching away from staff
            $record->syncPermissions([]);
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
