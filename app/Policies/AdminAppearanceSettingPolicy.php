<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AdminAppearanceSetting;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Politique d'accès aux paramètres d'apparence admin.
 */
class AdminAppearanceSettingPolicy
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
    return $authUser->can('ViewAny:AdminAppearanceSetting');
  }

  /**
   * Autorise la consultation d'un enregistrement.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param AdminAppearanceSetting $adminAppearanceSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function view(AuthUser $authUser, AdminAppearanceSetting $adminAppearanceSetting): bool
  {
    return $authUser->can('View:AdminAppearanceSetting');
  }

  /**
   * Autorise la création (désactivée côté resource).
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function create(AuthUser $authUser): bool
  {
    return $authUser->can('Create:AdminAppearanceSetting');
  }

  /**
   * Autorise la mise à jour des paramètres.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param AdminAppearanceSetting $adminAppearanceSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function update(AuthUser $authUser, AdminAppearanceSetting $adminAppearanceSetting): bool
  {
    return $authUser->can('Update:AdminAppearanceSetting');
  }

  /**
   * Interdit la suppression des paramètres globaux.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param AdminAppearanceSetting $adminAppearanceSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function delete(AuthUser $authUser, AdminAppearanceSetting $adminAppearanceSetting): bool
  {
    return $authUser->can('Delete:AdminAppearanceSetting');
  }

  /**
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function deleteAny(AuthUser $authUser): bool
  {
    return $authUser->can('DeleteAny:AdminAppearanceSetting');
  }

  /**
   * @param AuthUser $authUser Utilisateur connecté
   * @param AdminAppearanceSetting $adminAppearanceSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function restore(AuthUser $authUser, AdminAppearanceSetting $adminAppearanceSetting): bool
  {
    return $authUser->can('Restore:AdminAppearanceSetting');
  }

  /**
   * @param AuthUser $authUser Utilisateur connecté
   * @param AdminAppearanceSetting $adminAppearanceSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function forceDelete(AuthUser $authUser, AdminAppearanceSetting $adminAppearanceSetting): bool
  {
    return $authUser->can('ForceDelete:AdminAppearanceSetting');
  }

  /**
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function forceDeleteAny(AuthUser $authUser): bool
  {
    return $authUser->can('ForceDeleteAny:AdminAppearanceSetting');
  }

  /**
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function restoreAny(AuthUser $authUser): bool
  {
    return $authUser->can('RestoreAny:AdminAppearanceSetting');
  }

  /**
   * @param AuthUser $authUser Utilisateur connecté
   * @param AdminAppearanceSetting $adminAppearanceSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function replicate(AuthUser $authUser, AdminAppearanceSetting $adminAppearanceSetting): bool
  {
    return $authUser->can('Replicate:AdminAppearanceSetting');
  }

  /**
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function reorder(AuthUser $authUser): bool
  {
    return $authUser->can('Reorder:AdminAppearanceSetting');
  }
}
