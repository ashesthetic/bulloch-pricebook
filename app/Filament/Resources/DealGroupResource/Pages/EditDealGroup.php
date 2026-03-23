<?php

namespace App\Filament\Resources\DealGroupResource\Pages;

use App\Filament\Resources\DealGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDealGroup extends EditRecord
{
    protected static string $resource = DealGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
