<?php

namespace App\Services\Sms;

/**
 * Résultat d'un envoi SMS via Kecel.
 */
class KecelSmsResult
{
  /**
   * Initialise le résultat d'envoi.
   *
   * @param bool $success Indique si l'envoi est accepté
   * @param string $rawResponse Réponse brute de l'API
   * @param string|null $reference Référence fournie par Kecel
   * @param string|null $message Message d'état
   */
  public function __construct(
    public readonly bool $success,
    public readonly string $rawResponse,
    public readonly ?string $reference = null,
    public readonly ?string $message = null,
  ) {}
}
