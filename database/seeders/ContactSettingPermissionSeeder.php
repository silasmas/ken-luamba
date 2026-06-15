<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crée les permissions Shield pour la page contact.
 */
class ContactSettingPermissionSeeder extends Seeder
{
  /**
   * Alimente les permissions contact et les rattache au rôle super_admin.
   */
  public function run(): void
  {
    $permissions = [
      'ViewAny:ContactSetting',
      'View:ContactSetting',
      'Update:ContactSetting',
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
