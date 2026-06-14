<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crée les permissions Shield pour l'apparence admin et les assigne au super admin.
 */
class AdminAppearancePermissionSeeder extends Seeder
{
  /**
   * Alimente les permissions apparence et les rattache au rôle super_admin.
   */
  public function run(): void
  {
    $permissions = [
      'ViewAny:AdminAppearanceSetting',
      'View:AdminAppearanceSetting',
      'Update:AdminAppearanceSetting',
    ];

    foreach ($permissions as $permissionName) {
      Permission::query()->firstOrCreate([
        'name' => $permissionName,
        'guard_name' => 'web',
      ]);
    }

    $superAdmin = Role::query()->where('name', 'super_admin')->first();

    if ($superAdmin !== null) {
      $superAdmin->givePermissionTo($permissions);
    }
  }
}
