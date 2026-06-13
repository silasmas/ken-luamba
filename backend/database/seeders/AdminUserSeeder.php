<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crée le compte administrateur principal de la plateforme.
 */
class AdminUserSeeder extends Seeder
{
  /**
   * Alimente le compte admin Ken Luamba.
   */
  public function run(): void
  {
    User::query()->updateOrCreate(
      ['email' => 'admin@kenluamba.com'],
      [
        'name' => 'Admin Ken Luamba',
        'full_name' => 'Administrateur Ken Luamba',
        'password' => Hash::make('KenLuamba@2026'),
        'role' => UserRole::Admin,
        'is_active' => true,
        'email_verified_at' => now(),
      ]
    )->assignRole('super_admin');
  }
}
