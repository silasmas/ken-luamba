<?php

namespace App\Services\Users;

use App\Models\User;
use App\Support\PhoneNormalizer;

/**
 * Synchronise les numéros utilisateur depuis les paiements Mobile Money.
 */
class UserPhoneSyncService
{
  /**
   * Enregistre le numéro Mobile Money confirmé sur le profil client.
   *
   * @param User $user Client concerné
   * @param string $paymentPhone Numéro utilisé pour le paiement
   * @return void
   */
  public function syncFromMobileMoneyPayment(User $user, string $paymentPhone): void
  {
    $normalized = PhoneNormalizer::normalize($paymentPhone);

    if (! PhoneNormalizer::isValid($normalized)) {
      return;
    }

    if (blank($user->phone)) {
      $user->forceFill(['phone' => $normalized])->save();

      return;
    }

    if ($user->phone === $normalized || $user->secondary_phone === $normalized) {
      return;
    }

    $user->forceFill(['secondary_phone' => $normalized])->save();
  }
}
