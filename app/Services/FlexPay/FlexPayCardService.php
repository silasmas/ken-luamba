<?php

namespace App\Services\FlexPay;

use Illuminate\Support\Facades\Http;

/**
 * Service FlexPay pour les paiements par carte bancaire.
 */
class FlexPayCardService
{
  /**
   * Initie un paiement carte et retourne l'URL de redirection FlexPay.
   *
   * @param string $reference Référence unique de la commande
   * @param float $amount Montant à payer
   * @param string $currency Devise (CDF, USD)
   * @param string $description Description affichée
   * @return array{success: bool, url?: string, orderNumber?: string, message?: string}
   */
  public function initiate(
    string $reference,
    float $amount,
    string $currency,
    string $description,
  ): array {
    $token = config('services.flexpay.token');
    $merchant = config('services.flexpay.merchant');
    $gateway = config('services.flexpay.gateway_card');
    $frontendUrl = rtrim((string) env('FRONTEND_URL', env('APP_URL')), '/');

    if (empty($token) || empty($merchant) || empty($gateway)) {
      return [
        'success' => false,
        'message' => 'FlexPay carte non configuré.',
      ];
    }

    $baseRedirectUrl = $frontendUrl."/checkout/result?reference={$reference}&amount={$amount}&currency={$currency}";

    $body = [
      'authorization' => 'Bearer '.$token,
      'merchant' => $merchant,
      'reference' => $reference,
      'amount' => $amount,
      'currency' => $currency,
      'description' => $description,
      'callback_url' => url('/api/v1/payments/flexpay-callback'),
      'approve_url' => $baseRedirectUrl.'&status=success',
      'cancel_url' => $baseRedirectUrl.'&status=cancel',
      'decline_url' => $baseRedirectUrl.'&status=decline',
      'home_url' => $frontendUrl,
    ];

    $response = Http::withHeaders(['Content-Type' => 'application/json'])
      ->post($gateway, $body);

    $json = $response->json() ?? [];

    if (isset($json['code']) && (string) $json['code'] === '0') {
      return [
        'success' => true,
        'url' => $json['url'],
        'orderNumber' => $json['orderNumber'] ?? null,
      ];
    }

    return [
      'success' => false,
      'message' => $json['message'] ?? 'Échec initiation paiement carte.',
    ];
  }
}
