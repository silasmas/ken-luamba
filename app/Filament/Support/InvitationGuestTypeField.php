<?php

namespace App\Filament\Support;

use App\Enums\InvitationGuestType;
use Filament\Forms\Components\Select;

/**
 * Champ Filament réutilisable pour le type d'invité (VIP, VVIP, Autre).
 */
class InvitationGuestTypeField
{
  /**
   * Retourne un select configuré pour le type d'invité.
   *
   * @return Select Champ Filament
   */
  public static function make(): Select
  {
    return Select::make('organization')
      ->label('Type d\'invité')
      ->options(collect(InvitationGuestType::cases())->mapWithKeys(
        fn (InvitationGuestType $type) => [$type->value => $type->label()],
      )->all())
      ->native(false)
      ->placeholder('Sélectionner un type')
      ->helperText('VIP, VVIP ou Autre.');
  }
}
