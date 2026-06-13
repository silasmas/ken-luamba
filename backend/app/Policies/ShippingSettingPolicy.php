<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ShippingSetting;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Politique d'accès aux paramètres de livraison.
 */
class ShippingSettingPolicy
{
  use HandlesAuthorization;

  /**
   * Autorise l'accès au menu des paramètres livraison.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool Accès autorisé ou non
   */
  public function viewAny(AuthUser $authUser): bool
  {
    return $authUser->can('ViewAny:ShippingSetting');
  }

  /**
   * Autorise la consultation des paramètres.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingSetting $shippingSetting Enregistrement cible
   * @return bool Accès autorisé ou non
   */
  public function view(AuthUser $authUser, ShippingSetting $shippingSetting): bool
  {
    return $authUser->can('View:ShippingSetting');
  }

  /**
   * Interdit la création (enregistrement singleton).
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool Toujours false
   */
  public function create(AuthUser $authUser): bool
  {
    return false;
  }

  /**
   * Autorise la modification des paramètres.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingSetting $shippingSetting Enregistrement cible
   * @return bool Accès autorisé ou non
   */
  public function update(AuthUser $authUser, ShippingSetting $shippingSetting): bool
  {
    return $authUser->can('Update:ShippingSetting');
  }

  /**
   * Interdit la suppression des paramètres globaux.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingSetting $shippingSetting Enregistrement cible
   * @return bool Toujours false
   */
  public function delete(AuthUser $authUser, ShippingSetting $shippingSetting): bool
  {
    return false;
  }
}
