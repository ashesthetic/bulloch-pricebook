<?php

namespace App\Filament\Resources\MixAndMatchResource\Pages;

use App\Filament\Resources\MixAndMatchResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMixAndMatch extends EditRecord
{
    protected static string $resource = MixAndMatchResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
