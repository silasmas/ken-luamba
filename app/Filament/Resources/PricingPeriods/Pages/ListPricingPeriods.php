<?php

namespace App\Filament\Resources\PricingPeriods\Pages;

use App\Filament\Resources\PricingPeriods\PricingPeriodResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPricingPeriods extends ListRecords
{
    protected static string $resource = PricingPeriodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
