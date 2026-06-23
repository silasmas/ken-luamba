<?php

namespace App\Filament\Support;

use App\Services\Invitations\InvitationMessageService;
use Filament\Forms\Components\RichEditor;

/**
 * Configuration partagée des éditeurs riches pour les messages d'invitation.
 */
class InvitationRichEditorHelper
{
  /**
   * Applique la barre d'outils et les variables dynamiques à un RichEditor.
   *
   * @param RichEditor $editor Champ éditeur Filament
   * @return RichEditor Champ configuré
   */
  public static function configure(RichEditor $editor): RichEditor
  {
    return $editor
      ->mergeTags(self::mergeTags())
      ->toolbarButtons([
        ['bold', 'italic', 'underline', 'strike'],
        ['h2', 'h3'],
        ['alignStart', 'alignCenter', 'alignEnd'],
        ['bulletList', 'orderedList', 'blockquote'],
        ['mergeTags'],
        ['undo', 'redo'],
      ])
      ->helperText('Utilisez le panneau « Variables » ou copiez-collez les badges ci-dessus ({guest_name}, {event_date_short}, …). L\'aperçu SMS remplace toutes les variables avant envoi.');
  }

  /**
   * Retourne les merge tags Filament (identifiant => libellé affiché).
   *
   * @return array<string, string> Tags pour le RichEditor
   */
  public static function mergeTags(): array
  {
    $tags = [];

    foreach (InvitationMessageService::placeholderDefinitions() as $token => $description) {
      $tags[trim($token, '{}')] = $token;
    }

    return $tags;
  }
}
