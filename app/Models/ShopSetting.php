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
    'extra_contribution_enabled',
    'extra_contribution_label',
    'extra_contribution_help_text',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'currency' => ShopCurrency::class,
      'extra_contribution_enabled' => 'boolean',
    ];
  }

  /**
   * Retourne l'enregistrement unique des paramètres boutique.
   *
   * @return self Paramètres actifs ou valeurs par défaut
   */
  public static function instance(): self
  {
    return self::query()->firstOrCreate(
      [],
      [
        'currency' => ShopCurrency::Cdf->value,
        'extra_contribution_enabled' => false,
        'extra_contribution_label' => 'Soutien volontaire',
        'extra_contribution_help_text' => 'Ajoutez un montant libre au-delà du total de votre commande pour soutenir le ministère.',
      ],
    );
  }

  /**
   * Code devise ISO active pour toute la boutique.
   *
   * @return string CDF ou USD
   */
  public static function currencyCode(): string
  {
    $currency = self::instance()->currency;

    return $currency instanceof ShopCurrency ? $currency->value : (string) $currency;
  }
}
