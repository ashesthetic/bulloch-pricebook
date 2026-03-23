<?php

namespace App\Filament\Resources\MixAndMatchResource\Pages;

use App\Filament\Resources\MixAndMatchResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMixAndMatches extends ListRecords
{
    protected static string $resource = MixAndMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
