<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\DirectPaymentSetting;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Politique d'accès aux paramètres paiement direct.
 */
class DirectPaymentSettingPolicy
{
  use HandlesAuthorization;

  /**
   * Autorise la liste des paramètres.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function viewAny(AuthUser $authUser): bool
  {
    return $authUser->can('ViewAny:DirectPaymentSetting');
  }

  /**
   * Autorise la consultation.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param DirectPaymentSetting $directPaymentSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function view(AuthUser $authUser, DirectPaymentSetting $directPaymentSetting): bool
  {
    return $authUser->can('View:DirectPaymentSetting');
  }

  /**
   * Autorise la création.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function create(AuthUser $authUser): bool
  {
    return $authUser->can('Create:DirectPaymentSetting');
  }

  /**
   * Autorise la mise à jour.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param DirectPaymentSetting $directPaymentSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function update(AuthUser $authUser, DirectPaymentSetting $directPaymentSetting): bool
  {
    return $authUser->can('Update:DirectPaymentSetting');
  }

  /**
   * Autorise la suppression.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param DirectPaymentSetting $directPaymentSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function delete(AuthUser $authUser, DirectPaymentSetting $directPaymentSetting): bool
  {
    return $authUser->can('Delete:DirectPaymentSetting');
  }

  /**
   * Autorise la suppression en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function deleteAny(AuthUser $authUser): bool
  {
    return $authUser->can('DeleteAny:DirectPaymentSetting');
  }

  /**
   * Autorise la restauration.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param DirectPaymentSetting $directPaymentSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function restore(AuthUser $authUser, DirectPaymentSetting $directPaymentSetting): bool
  {
    return $authUser->can('Restore:DirectPaymentSetting');
  }

  /**
   * Autorise la suppression définitive.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param DirectPaymentSetting $directPaymentSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function forceDelete(AuthUser $authUser, DirectPaymentSetting $directPaymentSetting): bool
  {
    return $authUser->can('ForceDelete:DirectPaymentSetting');
  }

  /**
   * Autorise la suppression définitive en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function forceDeleteAny(AuthUser $authUser): bool
  {
    return $authUser->can('ForceDeleteAny:DirectPaymentSetting');
  }

  /**
   * Autorise la restauration en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function restoreAny(AuthUser $authUser): bool
  {
    return $authUser->can('RestoreAny:DirectPaymentSetting');
  }

  /**
   * Autorise la duplication.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param DirectPaymentSetting $directPaymentSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function replicate(AuthUser $authUser, DirectPaymentSetting $directPaymentSetting): bool
  {
    return $authUser->can('Replicate:DirectPaymentSetting');
  }

  /**
   * Autorise le réordonnancement.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function reorder(AuthUser $authUser): bool
  {
    return $authUser->can('Reorder:DirectPaymentSetting');
  }
}
