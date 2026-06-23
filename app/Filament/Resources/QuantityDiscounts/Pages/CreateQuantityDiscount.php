<?php

namespace App\Filament\Resources\QuantityDiscounts\Pages;

use App\Filament\Resources\QuantityDiscounts\Pages\Concerns\SyncsAuthorDiscountQuantity;
use App\Filament\Resources\QuantityDiscounts\QuantityDiscountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuantityDiscount extends CreateRecord
{
  use SyncsAuthorDiscountQuantity;

  protected static string $resource = QuantityDiscountResource::class;
}
