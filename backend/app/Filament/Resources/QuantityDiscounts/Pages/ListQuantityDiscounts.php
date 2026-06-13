<?php

namespace App\Filament\Resources\QuantityDiscounts\Pages;

use App\Filament\Resources\QuantityDiscounts\QuantityDiscountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuantityDiscounts extends ListRecords
{
    protected static string $resource = QuantityDiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
