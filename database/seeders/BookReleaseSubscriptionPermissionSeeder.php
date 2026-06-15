<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Permissions Filament pour la ressource alertes sortie.
 */
class BookReleaseSubscriptionPermissionSeeder extends Seeder
{
  /**
   * Crée les permissions et les attribue au rôle super_admin.
   */
  public function run(): void
  {
    $permissions = [
      'ViewAny:BookReleaseSubscription',
      'View:BookReleaseSubscription',
      'Delete:BookReleaseSubscription',
    ];

    foreach ($permissions as $permissionName) {
      Permission::query()->firstOrCreate([
        'name' => $permissionName,
        'guard_name' => 'web',
      ]);
    }

    $role = Role::query()->where('name', 'super_admin')->first();

    if ($role !== null) {
      $role->givePermissionTo($permissions);
    }
  }
}
