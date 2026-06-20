<?php

namespace App\Filament\Support;

use App\Services\Invitations\InvitationMessageService;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;

/**
 * Affiche les variables dynamiques d'invitation comme badges copiables avec infobulle.
 */
class InvitationPlaceholderHelper
{
  /**
   * Génère le HTML des variables cliquables avec infobulle au survol.
   *
   * @return HtmlString Markup des boutons variables
   */
  public static function toHtml(): HtmlString
  {
    $items = [];

    foreach (InvitationMessageService::placeholderDefinitions() as $token => $description) {
      $encodedToken = Js::from($token);
      $encodedDescription = e($description);

      $items[] = '<button type="button"'
        .' class="inline-flex items-center rounded-full border border-primary-200 bg-primary-50 px-2.5 py-1 text-xs font-semibold text-primary-800 shadow-sm transition hover:border-primary-400 hover:bg-primary-100 dark:border-primary-500/40 dark:bg-primary-950/50 dark:text-primary-100 dark:hover:border-primary-400 dark:hover:bg-primary-950"'
        .' title="'.$encodedDescription.'"'
        .' x-data="{ copied: false }"'
        .' x-on:click="navigator.clipboard.writeText('.$encodedToken.').then(() => { copied = true; setTimeout(() => copied = false, 1500) })"'
        .'>'
        .'<span x-show="!copied">'.e($token).'</span>'
        .'<span x-show="copied" x-cloak class="text-primary-600 dark:text-primary-400">Copié</span>'
        .'</button>';
    }

    return new HtmlString(
      '<div class="flex flex-wrap items-center gap-2">'.implode('', $items).'</div>'
        .'<p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Cliquez sur une variable pour la copier, ou insérez-la via le panneau « Variables » de l\'éditeur. Survolez un badge pour voir son utilisation.</p>',
    );
  }
}
