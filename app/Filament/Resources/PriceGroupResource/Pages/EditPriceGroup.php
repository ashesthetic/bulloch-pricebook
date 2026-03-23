<?php

namespace App\Filament\Resources\PriceGroupResource\Pages;

use App\Filament\Resources\PriceGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPriceGroup extends EditRecord
{
    protected static string $resource = PriceGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
