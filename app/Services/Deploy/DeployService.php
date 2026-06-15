<?php

namespace App\Services\Deploy;

use App\Models\User;
use Database\Seeders\ShippingPermissionSeeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

/**
 * Exécute les tâches de déploiement (migrations, Shield, storage…).
 */
class DeployService
{
  /**
   * Lance les migrations Laravel (--force).
   *
   * @return array{action: string, message: string, output: string}
   */
  public function migrate(): array
  {
    Artisan::call('migrate', ['--force' => true]);

    return [
      'action' => 'migrate',
      'message' => 'Migrations exécutées avec succès.',
      'output' => trim(Artisan::output()),
    ];
  }

  /**
   * Lance les seeders Laravel (--force).
   *
   * @return array{action: string, message: string, output: string}
   */
  public function seed(): array
  {
    Artisan::call('db:seed', ['--force' => true]);

    return [
      'action' => 'seed',
      'message' => 'Seeders exécutés avec succès.',
      'output' => trim(Artisan::output()),
    ];
  }

  /**
   * Crée le lien symbolique public/storage.
   *
   * @return array{action: string, message: string, output: string}
   */
  public function storageLink(): array
  {
    Artisan::call('storage:link', ['--force' => true]);

    return [
      'action' => 'storage',
      'message' => 'Lien symbolique storage créé avec succès.',
      'output' => trim(Artisan::output()),
    ];
  }

  /**
   * Génère les permissions Shield et assigne le super admin.
   *
   * @return array{action: string, message: string, output: array<string, string>}
   */
  public function shield(): array
  {
    Artisan::call('shield:generate', [
      '--all' => true,
      '--panel' => 'admin',
      '--no-interaction' => true,
    ]);
    $shieldOutput = trim(Artisan::output());

    Artisan::call('db:seed', [
      '--class' => ShippingPermissionSeeder::class,
      '--force' => true,
    ]);
    $shippingOutput = trim(Artisan::output());

    $superAdminOutput = 'Compte admin@kenluamba.com introuvable.';
    $adminUser = User::query()->where('email', 'admin@kenluamba.com')->first();

    if ($adminUser !== null) {
      Artisan::call('shield:super-admin', [
        '--user' => $adminUser->id,
        '--panel' => 'admin',
        '--no-interaction' => true,
      ]);
      $superAdminOutput = trim(Artisan::output());
    }

    return [
      'action' => 'shield',
      'message' => 'Permissions Shield générées avec succès.',
      'output' => [
        'shield_generate' => $shieldOutput,
        'shipping_permissions' => $shippingOutput,
        'super_admin' => $superAdminOutput,
      ],
    ];
  }

  /**
   * Exécute migrations, seeders et lien storage.
   *
   * @return array{action: string, message: string, output: array<string, string>}
   */
  public function setup(): array
  {
    Artisan::call('migrate', ['--force' => true]);
    $migrateOutput = trim(Artisan::output());

    Artisan::call('db:seed', ['--force' => true]);
    $seedOutput = trim(Artisan::output());

    Artisan::call('storage:link', ['--force' => true]);
    $storageOutput = trim(Artisan::output());

    Artisan::call('digital:ensure-files');
    $digitalOutput = trim(Artisan::output());

    return [
      'action' => 'setup',
      'message' => 'Migrations, seeders, storage et fichiers numériques exécutés avec succès.',
      'output' => [
        'migrate' => $migrateOutput,
        'seed' => $seedOutput,
        'storage' => $storageOutput,
        'digital_files' => $digitalOutput,
      ],
    ];
  }

  /**
   * Indique si le lien public/storage est présent.
   *
   * @return bool True si le lien ou le dossier existe
   */
  public function storageLinkExists(): bool
  {
    $publicStorage = public_path('storage');

    return File::exists($publicStorage);
  }

  /**
   * Formate la sortie d'une action pour affichage admin.
   *
   * @param array<string, mixed> $result Résultat d'une action deploy
   * @return string Texte lisible
   */
  public function formatOutput(array $result): string
  {
    $output = $result['output'] ?? '';

    if (is_array($output)) {
      $lines = [];

      foreach ($output as $key => $value) {
        $lines[] = strtoupper((string) $key)."\n".(string) $value;
      }

      return implode("\n\n", $lines);
    }

    return (string) $output;
  }
}
