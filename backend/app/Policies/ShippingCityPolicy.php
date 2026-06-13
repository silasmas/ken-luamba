<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\ShippingCity;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Foundation\Auth\User as AuthUser;

/**
 * Politique d'accès aux villes de livraison.
 */
class ShippingCityPolicy
{
  use HandlesAuthorization;

  /**
   * Autorise l'accès à la liste des villes.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool Accès autorisé ou non
   */
  public function viewAny(AuthUser $authUser): bool
  {
    return $authUser->can('ViewAny:ShippingCity');
  }

  /**
   * Autorise la consultation d'une ville.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingCity $shippingCity Ville cible
   * @return bool Accès autorisé ou non
   */
  public function view(AuthUser $authUser, ShippingCity $shippingCity): bool
  {
    return $authUser->can('View:ShippingCity');
  }

  /**
   * Autorise la création d'une ville.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool Accès autorisé ou non
   */
  public function create(AuthUser $authUser): bool
  {
    return $authUser->can('Create:ShippingCity');
  }

  /**
   * Autorise la modification d'une ville.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingCity $shippingCity Ville cible
   * @return bool Accès autorisé ou non
   */
  public function update(AuthUser $authUser, ShippingCity $shippingCity): bool
  {
    return $authUser->can('Update:ShippingCity');
  }

  /**
   * Autorise la suppression d'une ville.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingCity $shippingCity Ville cible
   * @return bool Accès autorisé ou non
   */
  public function delete(AuthUser $authUser, ShippingCity $shippingCity): bool
  {
    return $authUser->can('Delete:ShippingCity');
  }

  /**
   * Autorise la suppression en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool Accès autorisé ou non
   */
  public function deleteAny(AuthUser $authUser): bool
  {
    return $authUser->can('DeleteAny:ShippingCity');
  }

  /**
   * Autorise la restauration d'une ville.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingCity $shippingCity Ville cible
   * @return bool Accès autorisé ou non
   */
  public function restore(AuthUser $authUser, ShippingCity $shippingCity): bool
  {
    return $authUser->can('Restore:ShippingCity');
  }

  /**
   * Autorise la suppression définitive.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingCity $shippingCity Ville cible
   * @return bool Accès autorisé ou non
   */
  public function forceDelete(AuthUser $authUser, ShippingCity $shippingCity): bool
  {
    return $authUser->can('ForceDelete:ShippingCity');
  }

  /**
   * Autorise la suppression définitive en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool Accès autorisé ou non
   */
  public function forceDeleteAny(AuthUser $authUser): bool
  {
    return $authUser->can('ForceDeleteAny:ShippingCity');
  }

  /**
   * Autorise la restauration en masse.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool Accès autorisé ou non
   */
  public function restoreAny(AuthUser $authUser): bool
  {
    return $authUser->can('RestoreAny:ShippingCity');
  }

  /**
   * Autorise la duplication d'une ville.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @param ShippingCity $shippingCity Ville cible
   * @return bool Accès autorisé ou non
   */
  public function replicate(AuthUser $authUser, ShippingCity $shippingCity): bool
  {
    return $authUser->can('Replicate:ShippingCity');
  }

  /**
   * Autorise le réordonnancement des villes.
   *
   * @param AuthUser $authUser Utilisateur connecté
   * @return bool Accès autorisé ou non
   */
  public function reorder(AuthUser $authUser): bool
  {
    return $authUser->can('Reorder:ShippingCity');
  }
}
