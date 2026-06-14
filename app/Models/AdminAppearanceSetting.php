<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Paramètres d'apparence et de messagerie de l'administration (enregistrement unique).
 */
class AdminAppearanceSetting extends Model
{
  use HasUuids;

  /**
   * Valeurs par défaut lorsque la table n'est pas encore migrée.
   *
   * @var array<string, mixed>
   */
  public const DEFAULTS = [
    'site_title' => 'Ken Luamba',
    'logo_path' => null,
    'favicon_path' => null,
    'color_primary' => '#2563eb',
    'color_button_text' => '#ffffff',
    'color_body_text' => '#0f172a',
    'color_menu_active' => '#2563eb',
    'color_menu_active_text' => '#ffffff',
    'color_input_focus' => '#2563eb',
    'sidebar_collapsible' => true,
    'sms_manual_balance' => null,
  ];

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'site_title',
    'logo_path',
    'favicon_path',
    'color_primary',
    'color_button_text',
    'color_body_text',
    'color_menu_active',
    'color_menu_active_text',
    'color_input_focus',
    'sidebar_collapsible',
    'sms_manual_balance',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'sidebar_collapsible' => 'boolean',
      'sms_manual_balance' => 'integer',
    ];
  }

  /**
   * Retourne l'enregistrement unique des paramètres d'apparence.
   *
   * @return self Paramètres actifs ou valeurs par défaut
   */
  public static function instance(): self
  {
    if (! Schema::hasTable('admin_appearance_settings')) {
      return self::fallback();
    }

    return self::query()->firstOrCreate([], self::DEFAULTS);
  }

  /**
   * Retourne un modèle non persisté avec les valeurs par défaut.
   *
   * @return self Instance en mémoire
   */
  public static function fallback(): self
  {
    return (new self())->forceFill(self::DEFAULTS);
  }

  /**
   * URL publique du logo affiché dans l'admin.
   *
   * @return string URL du logo
   */
  public function logoUrl(): string
  {
    if (filled($this->logo_path)) {
      return asset('storage/'.$this->logo_path);
    }

    return asset('images/logo-kl-black.png');
  }

  /**
   * URL publique du favicon de l'admin.
   *
   * @return string URL du favicon
   */
  public function faviconUrl(): string
  {
    if (filled($this->favicon_path)) {
      return asset('storage/'.$this->favicon_path);
    }

    return asset('images/logo-kl.png');
  }
}
