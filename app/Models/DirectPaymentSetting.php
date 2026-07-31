<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Paramètres globaux du module paiement direct (enregistrement unique).
 */
class DirectPaymentSetting extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'is_enabled',
    'title',
    'message',
    'pack_book_format_ids',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'is_enabled' => 'boolean',
      'pack_book_format_ids' => 'array',
    ];
  }

  /**
   * Retourne l'enregistrement unique des paramètres paiement direct.
   *
   * @return self Paramètres actifs ou valeurs par défaut
   */
  public static function instance(): self
  {
    return self::query()->firstOrCreate(
      [],
      [
        'is_enabled' => true,
        'title' => 'Paiement direct',
        'message' => 'Sélectionnez vos livres, saisissez votre email et payez par carte ou Mobile Money.',
        'pack_book_format_ids' => [],
      ],
    );
  }

  /**
   * URL publique de la page paiement direct côté frontend.
   *
   * @return string URL absolue
   */
  public function publicUrl(): string
  {
    $frontendUrl = rtrim((string) config('app.frontend_url', env('FRONTEND_URL', 'http://localhost:3001')), '/');

    return $frontendUrl.'/paiement-direct';
  }
}
