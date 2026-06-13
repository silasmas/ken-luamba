<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Page admin listant la documentation des endpoints API.
 */
class ApiDocumentation extends Page
{
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

  protected static ?string $navigationLabel = 'Documentation API';

  protected static ?string $title = 'Documentation API';

  protected static string|UnitEnum|null $navigationGroup = 'Système';

  protected static ?int $navigationSort = 99;

  protected string $view = 'filament.pages.api-documentation';
}
