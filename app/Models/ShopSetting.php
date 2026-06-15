<?php

namespace App\Models;

use App\Enums\ShopCurrency;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Paramètres globaux de la boutique (enregistrement unique).
 */
class ShopSetting extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'currency',
  ];

  /**
   * Retourne l'enregistrement unique des paramètres boutique.
   *
   * @return self Paramètres actifs ou valeurs par défaut
   */
  public static function instance(): self
  {
    return self::query()->firstOrCreate(
      [],
      ['currency' => ShopCurrency::Cdf->value],
    );
  }

  /**
   * Code devise ISO active pour toute la boutique.
   *
   * @return string CDF ou USD
   */
  public static function currencyCode(): string
  {
    return self::instance()->currency;
  }
}
