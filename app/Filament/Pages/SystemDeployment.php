<?php

namespace App\Filament\Pages;

use App\Services\Deploy\DeployService;
use App\Support\DeploySeederRegistry;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

/**
 * Page admin pour exécuter migrations, Shield et lien storage.
 */
class SystemDeployment extends Page
{
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

  protected static ?string $navigationLabel = 'Déploiement';

  protected static ?string $title = 'Outils de déploiement';

  protected static string|UnitEnum|null $navigationGroup = 'Système';

  protected static ?int $navigationSort = 1;

  protected string $view = 'filament.pages.system-deployment';

  public ?string $lastAction = null;

  public ?string $lastOutput = null;

  public bool $storageLinked = false;

  /** @var list<array{class: class-string, key: string, label: string, group: string, description: string}> */
  public array $availableSeeders = [];

  /**
   * Restreint l'accès aux super administrateurs.
   *
   * @return bool True si l'utilisateur peut ouvrir la page
   */
  public static function canAccess(): bool
  {
    $user = auth()->user();

    return $user !== null && $user->hasRole('super_admin');
  }

  /**
   * Initialise l'état de la page (lien storage).
   */
  public function mount(DeployService $deployService): void
  {
    $this->storageLinked = $deployService->storageLinkExists();
    $this->availableSeeders = $deployService->availableSeeders();
  }

  /**
   * Actions d'en-tête : migrations, Shield, storage, setup.
   *
   * @return array<int, Action>
   */
  protected function getHeaderActions(): array
  {
    return [
      Action::make('migrate')
        ->label('Migrations')
        ->icon(Heroicon::OutlinedArrowPath)
        ->color('gray')
        ->requiresConfirmation()
        ->modalHeading('Exécuter les migrations ?')
        ->modalDescription('Lance php artisan migrate --force sur ce serveur.')
        ->action(fn () => $this->runDeployAction('migrate')),
      Action::make('shield')
        ->label('Permissions Shield')
        ->icon(Heroicon::OutlinedShieldCheck)
        ->color('info')
        ->requiresConfirmation()
        ->modalHeading('Générer les permissions Shield ?')
        ->modalDescription('Génère les permissions Filament, les droits livraison et assigne super_admin.')
        ->action(fn () => $this->runDeployAction('shield')),
      Action::make('storage')
        ->label('Lien storage')
        ->icon(Heroicon::OutlinedLink)
        ->color('warning')
        ->requiresConfirmation()
        ->modalHeading('Créer le lien storage ?')
        ->modalDescription('Lance php artisan storage:link --force (obligatoire pour afficher les images).')
        ->action(fn () => $this->runDeployAction('storage')),
      Action::make('seed')
        ->label('Seeders')
        ->icon(Heroicon::OutlinedCircleStack)
        ->color('gray')
        ->form([
          CheckboxList::make('seeders')
            ->label('Seeders à exécuter')
            ->options(DeploySeederRegistry::options())
            ->columns(2)
            ->bulkToggleable()
            ->required()
            ->helperText('Sélectionnez un ou plusieurs seeders. « Tous » exécute DatabaseSeeder.'),
        ])
        ->modalHeading('Exécuter des seeders ciblés')
        ->modalDescription('Chaque seeder sélectionné sera lancé individuellement avec --force.')
        ->action(function (array $data): void {
          $this->runSeedSelected($data['seeders'] ?? []);
        }),
      Action::make('seedAll')
        ->label('Tous les seeders')
        ->icon(Heroicon::OutlinedCircleStack)
        ->color('gray')
        ->requiresConfirmation()
        ->modalHeading('Exécuter tous les seeders ?')
        ->modalDescription('Lance php artisan db:seed --force (DatabaseSeeder complet).')
        ->action(fn () => $this->runDeployAction('seed')),
      Action::make('setup')
        ->label('Setup complet')
        ->icon(Heroicon::OutlinedRocketLaunch)
        ->color('success')
        ->requiresConfirmation()
        ->modalHeading('Lancer le setup complet ?')
        ->modalDescription('Migrations + seeders + lien storage en une seule opération.')
        ->action(fn () => $this->runDeployAction('setup')),
    ];
  }

  /**
   * Exécute des seeders sélectionnés depuis le formulaire modal.
   *
   * @param list<string> $seederKeys Clés DeploySeederRegistry
   * @return void
   */
  private function runSeedSelected(array $seederKeys): void
  {
    $deployService = app(DeployService::class);

    try {
      $result = $deployService->seedSelected($seederKeys);

      $this->lastAction = $result['action'];
      $this->lastOutput = $deployService->formatOutput($result);

      Notification::make()
        ->title($result['message'])
        ->success()
        ->send();
    } catch (\Throwable $exception) {
      report($exception);

      $this->lastAction = 'seed';
      $this->lastOutput = $exception->getMessage();

      Notification::make()
        ->title('Échec des seeders')
        ->body($exception->getMessage())
        ->danger()
        ->send();
    }
  }

  /**
   * Exécute une action de déploiement et met à jour l'affichage.
   *
   * @param string $action Nom de l'action (migrate, shield, storage, seed, setup)
   * @return void
   */
  private function runDeployAction(string $action): void
  {
    $deployService = app(DeployService::class);

    try {
      $result = match ($action) {
        'migrate' => $deployService->migrate(),
        'shield' => $deployService->shield(),
        'storage' => $deployService->storageLink(),
        'seed' => $deployService->seed(),
        'setup' => $deployService->setup(),
        default => throw new \InvalidArgumentException('Action inconnue.'),
      };

      $this->lastAction = $result['action'];
      $this->lastOutput = $deployService->formatOutput($result);
      $this->storageLinked = $deployService->storageLinkExists();

      Notification::make()
        ->title($result['message'])
        ->success()
        ->send();
    } catch (\Throwable $exception) {
      report($exception);

      $this->lastAction = $action;
      $this->lastOutput = $exception->getMessage();

      Notification::make()
        ->title('Échec de l\'action de déploiement')
        ->body($exception->getMessage())
        ->danger()
        ->send();
    }
  }
}
