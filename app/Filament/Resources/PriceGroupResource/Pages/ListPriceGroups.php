<?php

namespace App\Filament\Resources\PriceGroupResource\Pages;

use App\Filament\Resources\PriceGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPriceGroups extends ListRecords
{
    protected static string $resource = PriceGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
