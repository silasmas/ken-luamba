<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crée un compte livreur de démonstration.
 */
class CourierUserSeeder extends Seeder
{
  /**
   * Alimente le livreur de test.
   */
  public function run(): void
  {
    User::query()->updateOrCreate(
      ['email' => 'livreur@kenluamba.com'],
      [
        'name' => 'Livreur Demo',
        'full_name' => 'Jean Livreur',
        'phone' => '243900000099',
        'password' => Hash::make('Livreur@2026'),
        'role' => UserRole::Courier,
        'is_active' => true,
        'email_verified_at' => now(),
      ]
    );
  }
}
