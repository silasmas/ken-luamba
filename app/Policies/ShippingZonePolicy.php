<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ShippingZone;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Politique d'accès aux zones de livraison.
 */
class ShippingZonePolicy
{
  use HandlesAuthorization;

  /**
   * Autorise l'accès à la liste des zones.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool Accès autorisé ou non
   */
  public function viewAny(AuthUser $authUser): bool
  {
    return $authUser->can('ViewAny:ShippingZone');
  }

  /**
   * Autorise la consultation d'une zone.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingZone $shippingZone Zone cible
   * @return bool Accès autorisé ou non
   */
  public function view(AuthUser $authUser, ShippingZone $shippingZone): bool
  {
    return $authUser->can('View:ShippingZone');
  }

  /**
   * Autorise la création d'une zone.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool Accès autorisé ou non
   */
  public function create(AuthUser $authUser): bool
  {
    return $authUser->can('Create:ShippingZone');
  }

  /**
   * Autorise la modification d'une zone.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingZone $shippingZone Zone cible
   * @return bool Accès autorisé ou non
   */
  public function update(AuthUser $authUser, ShippingZone $shippingZone): bool
  {
    return $authUser->can('Update:ShippingZone');
  }

  /**
   * Autorise la suppression d'une zone.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingZone $shippingZone Zone cible
   * @return bool Accès autorisé ou non
   */
  public function delete(AuthUser $authUser, ShippingZone $shippingZone): bool
  {
    return $authUser->can('Delete:ShippingZone');
  }

  /**
   * Autorise la suppression en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool Accès autorisé ou non
   */
  public function deleteAny(AuthUser $authUser): bool
  {
    return $authUser->can('DeleteAny:ShippingZone');
  }

  /**
   * Autorise la restauration d'une zone.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingZone $shippingZone Zone cible
   * @return bool Accès autorisé ou non
   */
  public function restore(AuthUser $authUser, ShippingZone $shippingZone): bool
  {
    return $authUser->can('Restore:ShippingZone');
  }

  /**
   * Autorise la suppression définitive.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingZone $shippingZone Zone cible
   * @return bool Accès autorisé ou non
   */
  public function forceDelete(AuthUser $authUser, ShippingZone $shippingZone): bool
  {
    return $authUser->can('ForceDelete:ShippingZone');
  }

  /**
   * Autorise la suppression définitive en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool Accès autorisé ou non
   */
  public function forceDeleteAny(AuthUser $authUser): bool
  {
    return $authUser->can('ForceDeleteAny:ShippingZone');
  }

  /**
   * Autorise la restauration en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool Accès autorisé ou non
   */
  public function restoreAny(AuthUser $authUser): bool
  {
    return $authUser->can('RestoreAny:ShippingZone');
  }

  /**
   * Autorise la duplication d'une zone.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingZone $shippingZone Zone cible
   * @return bool Accès autorisé ou non
   */
  public function replicate(AuthUser $authUser, ShippingZone $shippingZone): bool
  {
    return $authUser->can('Replicate:ShippingZone');
  }

  /**
   * Autorise le réordonnancement des zones.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool Accès autorisé ou non
   */
  public function reorder(AuthUser $authUser): bool
  {
    return $authUser->can('Reorder:ShippingZone');
  }
}
