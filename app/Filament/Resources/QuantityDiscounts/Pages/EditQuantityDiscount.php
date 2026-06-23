<?php

namespace App\Filament\Resources\QuantityDiscounts\Pages;

use App\Filament\Resources\QuantityDiscounts\Pages\Concerns\SyncsAuthorDiscountQuantity;
use App\Filament\Resources\QuantityDiscounts\QuantityDiscountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuantityDiscount extends EditRecord
{
  use SyncsAuthorDiscountQuantity;

  protected static string $resource = QuantityDiscountResource::class;

  protected function getHeaderActions(): array
  {
    return [
      DeleteAction::make(),
    ];
  }
}
