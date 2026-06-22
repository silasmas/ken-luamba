<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;

/**
 * Champ Filament réutilisable pour le type d'invité (texte libre).
 */
class InvitationGuestTypeField
{
  /**
   * Retourne un champ texte pour saisir librement le type d'invité.
   *
   * @return TextInput Champ Filament
   */
  public static function make(): TextInput
  {
    return TextInput::make('organization')
      ->label('Type d\'invité')
      ->maxLength(255)
      ->placeholder('Ex. VIP, VVIP, Presse, Partenaire…')
      ->helperText('Texte libre affiché dans le tableau et sur la page d\'invitation.');
  }
}
