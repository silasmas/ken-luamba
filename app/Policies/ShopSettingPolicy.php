<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ShopSetting;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Politique d'accès aux paramètres boutique.
 */
class ShopSettingPolicy
{
  use HandlesAuthorization;

  /**
   * Autorise la liste des paramètres boutique.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function viewAny(AuthUser $authUser): bool
  {
    return $authUser->can('ViewAny:ShopSetting');
  }

  /**
   * Autorise la consultation des paramètres boutique.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShopSetting $shopSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function view(AuthUser $authUser, ShopSetting $shopSetting): bool
  {
    return $authUser->can('View:ShopSetting');
  }

  /**
   * Autorise la création des paramètres boutique.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function create(AuthUser $authUser): bool
  {
    return $authUser->can('Create:ShopSetting');
  }

  /**
   * Autorise la mise à jour des paramètres boutique.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShopSetting $shopSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function update(AuthUser $authUser, ShopSetting $shopSetting): bool
  {
    return $authUser->can('Update:ShopSetting');
  }

  /**
   * Autorise la suppression des paramètres boutique.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShopSetting $shopSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function delete(AuthUser $authUser, ShopSetting $shopSetting): bool
  {
    return $authUser->can('Delete:ShopSetting');
  }

  /**
   * Autorise la suppression en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function deleteAny(AuthUser $authUser): bool
  {
    return $authUser->can('DeleteAny:ShopSetting');
  }

  /**
   * Autorise la restauration.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShopSetting $shopSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function restore(AuthUser $authUser, ShopSetting $shopSetting): bool
  {
    return $authUser->can('Restore:ShopSetting');
  }

  /**
   * Autorise la suppression définitive.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShopSetting $shopSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function forceDelete(AuthUser $authUser, ShopSetting $shopSetting): bool
  {
    return $authUser->can('ForceDelete:ShopSetting');
  }

  /**
   * Autorise la suppression définitive en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function forceDeleteAny(AuthUser $authUser): bool
  {
    return $authUser->can('ForceDeleteAny:ShopSetting');
  }

  /**
   * Autorise la restauration en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function restoreAny(AuthUser $authUser): bool
  {
    return $authUser->can('RestoreAny:ShopSetting');
  }

  /**
   * Autorise la duplication.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShopSetting $shopSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function replicate(AuthUser $authUser, ShopSetting $shopSetting): bool
  {
    return $authUser->can('Replicate:ShopSetting');
  }

  /**
   * Autorise le réordonnancement.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function reorder(AuthUser $authUser): bool
  {
    return $authUser->can('Reorder:ShopSetting');
  }
}
