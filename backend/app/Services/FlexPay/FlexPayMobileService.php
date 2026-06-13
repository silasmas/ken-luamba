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
    $transaction = $json['transaction'] ?? [];

    return [
      'status' => (int) ($transaction['status'] ?? -1),
      'message' => (string) ($json['message'] ?? 'Statut inconnu'),
      'reference' => $transaction['reference'] ?? null,
    ];
  }
}
