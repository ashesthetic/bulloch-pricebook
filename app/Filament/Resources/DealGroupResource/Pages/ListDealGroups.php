<?php

namespace App\Filament\Resources\DealGroupResource\Pages;

use App\Filament\Resources\DealGroupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDealGroups extends ListRecords
{
    protected static string $resource = DealGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
