<?php

use App\Enums\ShopCurrency;

return [
  /**
   * Devises proposées aux clients et dans l'admin.
   */
  'currencies' => array_map(
    fn (ShopCurrency $currency): string => $currency->value,
    ShopCurrency::cases(),
  ),
];
