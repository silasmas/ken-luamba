<?php

namespace App\Filament\Concerns;

use Filament\Support\Enums\Width;

/**
 * Affiche le contenu des pages Filament en pleine largeur.
 */
trait HasFullWidthContent
{
  protected Width | string | null $maxContentWidth = Width::Full;
}
