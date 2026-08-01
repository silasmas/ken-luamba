<?php

namespace App\Services\FlexPay;

use Illuminate\Support\Facades\Http;

/**
 * Service FlexPay pour les paiements Mobile Money (type API "1").
 */
class FlexPayMobileService
{
  /**
   * Initie un paiement Mobile Money via paymentService FlexPay.
   *
   * @param string $reference Référence marchande unique
   * @param float $amount Montant
   * @param string $currency Devise
   * @param string $phone Numéro 12 chiffres (243…)
   * @return array{success: bool, message: string, orderNumber?: string}
   */
  public function initiate(
    string $reference,
    float $amount,
    string $currency,
    string $phone,
  ): array {
    $token = config('services.flexpay.token');
    $url = config('services.flexpay.gateway_mobile');
    $merchant = config('services.flexpay.merchant');
    $type = (string) config('flexpay.flexpay_mobile_money_api_type', '1');

    if (empty($token) || empty($url) || empty($merchant)) {
      return [
        'success' => false,
        'message' => 'Passerelle de paiement mobile non configurée.',
      ];
    }

    $body = [
      'merchant' => $merchant,
      'type' => $type,
      'phone' => $phone,
      'reference' => $reference,
      'amount' => $amount,
      'currency' => $currency,
      'callbackUrl' => url('/api/v1/payments/flexpay-callback'),
    ];

    $response = Http::withHeaders([
      'Content-Type' => 'application/json',
      'Authorization' => 'Bearer '.$token,
    ])->post($url, $body);

    $payload = $response->json() ?? [];

    if (isset($payload['code']) && (string) $payload['code'] === '0') {
      return [
        'success' => true,
        'message' => (string) ($payload['message'] ?? 'Validez le paiement sur votre téléphone.'),
        'orderNumber' => $payload['orderNumber'] ?? null,
      ];
    }

    return [
      'success' => false,
      'message' => (string) ($payload['message'] ?? 'Paiement mobile refusé.'),
    ];
  }

  /**
   * Vérifie le statut d'une transaction FlexPay.
   *
   * @param string $orderNumber Référence FlexPay (orderNumber)
   * @return array{status: int, message: string, reference?: string}
   */
  public function checkStatus(string $orderNumber): array
  {
    $token = config('services.flexpay.token');
    $base = rtrim((string) config('services.flexpay.gateway_check'), '/');
    $url = $base.'/'.urlencode($orderNumber);

    $response = Http::withHeaders([
      'Authorization' => 'Bearer '.$token,
    ])->get($url);

    $json = $response->json() ?? [];
    $transaction = is_array($json['transaction'] ?? null) ? $json['transaction'] : [];

    // FlexPay : 0=payé, 1=annulé/refusé, 2=en attente (parfois string ou à la racine).
    $rawStatus = $transaction['status']
      ?? $json['transactionStatus']
      ?? $json['status']
      ?? null;

    $status = is_numeric($rawStatus) ? (int) $rawStatus : -1;
    $message = (string) ($json['message'] ?? $transaction['message'] ?? 'Statut inconnu');

    if ($status === -1 && $this->messageLooksFailed($message)) {
      $status = 1;
    }

    return [
      'status' => $status,
      'message' => $message,
      'reference' => $transaction['reference'] ?? $json['reference'] ?? null,
    ];
  }

  /**
   * Détecte un échec / annulation dans le message FlexPay quand le code statut manque.
   *
   * @param string $message Message API
   * @return bool True si le libellé indique un refus
   */
  private function messageLooksFailed(string $message): bool
  {
    $haystack = mb_strtolower($message);

    return str_contains($haystack, 'annul')
      || str_contains($haystack, 'refus')
      || str_contains($haystack, 'échou')
      || str_contains($haystack, 'echec')
      || str_contains($haystack, 'fail')
      || str_contains($haystack, 'cancel')
      || str_contains($haystack, 'decline');
  }
}
