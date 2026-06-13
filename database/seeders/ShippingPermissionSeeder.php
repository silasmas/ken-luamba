<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Crée les permissions Shield pour la livraison et les assigne au super admin.
 */
class ShippingPermissionSeeder extends Seeder
{
  /**
   * Alimente les permissions livraison et les rattache au rôle super_admin.
   */
  public function run(): void
  {
    $permissions = [
      'ViewAny:ShippingSetting',
      'View:ShippingSetting',
      'Update:ShippingSetting',
      'ViewAny:ShippingCity',
      'View:ShippingCity',
      'Create:ShippingCity',
      'Update:ShippingCity',
      'Delete:ShippingCity',
      'DeleteAny:ShippingCity',
      'Restore:ShippingCity',
      'RestoreAny:ShippingCity',
      'ForceDelete:ShippingCity',
      'ForceDeleteAny:ShippingCity',
      'Replicate:ShippingCity',
      'Reorder:ShippingCity',
      'ViewAny:ShippingZone',
      'View:ShippingZone',
      'Create:ShippingZone',
      'Update:ShippingZone',
      'Delete:ShippingZone',
      'DeleteAny:ShippingZone',
      'Restore:ShippingZone',
      'RestoreAny:ShippingZone',
      'ForceDelete:ShippingZone',
      'ForceDeleteAny:ShippingZone',
      'Replicate:ShippingZone',
      'Reorder:ShippingZone',
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
