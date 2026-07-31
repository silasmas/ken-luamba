<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crée les permissions Shield pour les paramètres paiement direct.
 */
class DirectPaymentSettingPermissionSeeder extends Seeder
{
  /**
   * Alimente les permissions et les rattache au rôle super_admin.
   */
  public function run(): void
  {
    $permissions = [
      'ViewAny:DirectPaymentSetting',
      'View:DirectPaymentSetting',
      'Update:DirectPaymentSetting',
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
