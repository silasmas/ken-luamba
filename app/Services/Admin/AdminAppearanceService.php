<?php

namespace App\Services\Admin;

use App\Models\AdminAppearanceSetting;

/**
 * Génère le CSS personnalisé de l'interface d'administration Filament.
 */
class AdminAppearanceService
{
  /**
   * Retourne les paramètres d'apparence actifs.
   *
   * @return AdminAppearanceSetting Paramètres chargés
   */
  public function settings(): AdminAppearanceSetting
  {
    return AdminAppearanceSetting::instance();
  }

  /**
   * Construit la feuille de style injectée dans le panel admin.
   *
   * @return string Bloc CSS personnalisé
   */
  public function buildCustomCss(): string
  {
    $settings = $this->settings();

    $primary = $this->sanitizeHexColor($settings->color_primary, '#2563eb');
    $buttonText = $this->sanitizeHexColor($settings->color_button_text, '#ffffff');
    $bodyText = $this->sanitizeHexColor($settings->color_body_text, '#0f172a');
    $menuActive = $this->sanitizeHexColor($settings->color_menu_active, $primary);
    $menuActiveText = $this->sanitizeHexColor($settings->color_menu_active_text, '#ffffff');
    $inputFocus = $this->sanitizeHexColor($settings->color_input_focus, $primary);

    return <<<CSS
      .fi-body {
        --font-family: ui-sans-serif, system-ui, sans-serif;
        color: {$bodyText};
      }

      .fi-login-page .fi-simple-main {
        background: linear-gradient(135deg, #f8fafc 0%, #eff6ff 55%, #ffffff 100%);
      }

      .fi-sidebar-header .fi-logo {
        object-fit: contain;
      }

      .fi-btn.fi-btn-color-primary,
      .fi-btn.fi-color-primary {
        color: {$buttonText} !important;
      }

      .fi-sidebar-item.fi-active > .fi-sidebar-item-btn {
        background-color: {$menuActive} !important;
      }

      .fi-sidebar-item.fi-active .fi-sidebar-item-label,
      .fi-sidebar-item.fi-active .fi-sidebar-item-icon {
        color: {$menuActiveText} !important;
      }

      .fi-input-wrp:focus-within {
        --tw-ring-color: {$inputFocus} !important;
        border-color: {$inputFocus} !important;
      }

      .fi-fo-field-wrp:focus-within .fi-input-wrp {
        --tw-ring-color: {$inputFocus} !important;
        border-color: {$inputFocus} !important;
      }

      .fi-topbar-item-btn.fi-active,
      .fi-tabs-item.fi-active {
        color: {$primary} !important;
      }
    CSS;
  }

  /**
   * Nettoie et valide une couleur hexadécimale.
   *
   * @param string|null $color Couleur saisie
   * @param string $fallback Couleur de repli
   * @return string Couleur hex valide
   */
  private function sanitizeHexColor(?string $color, string $fallback): string
  {
    if (! is_string($color) || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
      return $fallback;
    }

    return $color;
  }
}
