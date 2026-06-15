<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * Paramètres de contact affichés sur la page publique (enregistrement unique).
 */
class ContactSetting extends Model
{
  use HasUuids;

  /**
   * Valeurs par défaut alignées sur le contenu initial du site.
   *
   * @var array<string, string>
   */
  public const DEFAULTS = [
    'phone_primary' => '+243 (0) 82 10 14 878',
    'phone_secondary' => '+243 (0) 82 44 40 674',
    'email' => 'kenluamba@gmail.com',
    'physical_address' => '4524, Avenue Des Forces Armées (ex. Haut-Commandement), C/ Gombe Kinshasa • RD Congo',
    'intro_description' => 'Pour toute demande — ministère, conférences, presse ou édition — notre équipe vous répond avec attention.',
    'show_sdev_credit' => true,
    'sdev_label' => 'SDev',
    'sdev_url' => 'https://silasmas.com',
  ];

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'phone_primary',
    'phone_secondary',
    'email',
    'physical_address',
    'intro_description',
    'show_sdev_credit',
    'sdev_label',
    'sdev_url',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'show_sdev_credit' => 'boolean',
    ];
  }

  /**
   * Retourne l'enregistrement unique des paramètres de contact.
   *
   * @return self Paramètres actifs ou valeurs par défaut
   */
  public static function instance(): self
  {
    return self::query()->firstOrCreate([], self::DEFAULTS);
  }
}
