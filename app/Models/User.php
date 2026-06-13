<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'full_name', 'email', 'phone', 'role', 'password', 'is_active', 'avatar_path', 'profile_address', 'delivery_address'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser
{
  /** @use HasFactory<UserFactory> */
  use HasApiTokens, HasFactory, HasRoles, Notifiable;

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
      'role' => UserRole::class,
      'is_active' => 'boolean',
      'profile_address' => 'array',
      'delivery_address' => 'array',
    ];
  }

  /**
   * Détermine si l'utilisateur peut accéder au panel Filament demandé.
   *
   * @param Panel $panel Panel Filament cible
   * @return bool True si l'accès est autorisé
   */
  public function canAccessPanel(Panel $panel): bool
  {
    if (! $this->is_active) {
      return false;
    }

    if ($panel->getId() !== 'admin') {
      return false;
    }

    return $this->hasRole('super_admin')
      || $this->hasRole('panel_user')
      || $this->role === UserRole::Admin
      || $this->role === UserRole::Editor;
  }
}
