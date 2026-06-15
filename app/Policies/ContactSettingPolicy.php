<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ContactSetting;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Politique d'accès aux paramètres de contact.
 */
class ContactSettingPolicy
{
  use HandlesAuthorization;

  /**
   * Autorise la liste des paramètres de contact.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function viewAny(AuthUser $authUser): bool
  {
    return $authUser->can('ViewAny:ContactSetting');
  }

  /**
   * Autorise la consultation des paramètres de contact.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ContactSetting $contactSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function view(AuthUser $authUser, ContactSetting $contactSetting): bool
  {
    return $authUser->can('View:ContactSetting');
  }

  /**
   * Autorise la création des paramètres de contact.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function create(AuthUser $authUser): bool
  {
    return $authUser->can('Create:ContactSetting');
  }

  /**
   * Autorise la mise à jour des paramètres de contact.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ContactSetting $contactSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function update(AuthUser $authUser, ContactSetting $contactSetting): bool
  {
    return $authUser->can('Update:ContactSetting');
  }

  /**
   * Autorise la suppression des paramètres de contact.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ContactSetting $contactSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function delete(AuthUser $authUser, ContactSetting $contactSetting): bool
  {
    return $authUser->can('Delete:ContactSetting');
  }

  /**
   * Autorise la suppression en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function deleteAny(AuthUser $authUser): bool
  {
    return $authUser->can('DeleteAny:ContactSetting');
  }

  /**
   * Autorise la restauration.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ContactSetting $contactSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function restore(AuthUser $authUser, ContactSetting $contactSetting): bool
  {
    return $authUser->can('Restore:ContactSetting');
  }

  /**
   * Autorise la suppression définitive.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ContactSetting $contactSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function forceDelete(AuthUser $authUser, ContactSetting $contactSetting): bool
  {
    return $authUser->can('ForceDelete:ContactSetting');
  }

  /**
   * Autorise la suppression définitive en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function forceDeleteAny(AuthUser $authUser): bool
  {
    return $authUser->can('ForceDeleteAny:ContactSetting');
  }

  /**
   * Autorise la restauration en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function restoreAny(AuthUser $authUser): bool
  {
    return $authUser->can('RestoreAny:ContactSetting');
  }

  /**
   * Autorise la duplication.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ContactSetting $contactSetting Paramètres ciblés
   * @return bool True si autorisé
   */
  public function replicate(AuthUser $authUser, ContactSetting $contactSetting): bool
  {
    return $authUser->can('Replicate:ContactSetting');
  }

  /**
   * Autorise le réordonnancement.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool True si autorisé
   */
  public function reorder(AuthUser $authUser): bool
  {
    return $authUser->can('Reorder:ContactSetting');
  }
}
