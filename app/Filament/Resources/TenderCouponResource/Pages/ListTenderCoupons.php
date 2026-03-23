<?php

namespace App\Filament\Resources\TenderCouponResource\Pages;

use App\Filament\Resources\TenderCouponResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTenderCoupons extends ListRecords
{
    protected static string $resource = TenderCouponResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
