<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Client SMS Kecel (paramètres dans config/sms.php et .env).
 */
class KecelSmsService
{
  /**
   * Envoie un SMS via l'API Kecel.
   *
   * @param string $phone Numéro international sans +
   * @param string $message Contenu du SMS
   * @return KecelSmsResult Résultat de l'envoi
   */
  public function send(string $phone, string $message): KecelSmsResult
  {
    $token = (string) config('sms.token');
    $from = (string) config('sms.from');
    $url = (string) config('sms.url');

    if ($token === '' || $from === '') {
      throw new RuntimeException('Configuration SMS incomplète (SMS_TOKEN ou SMS_FROM manquant).');
    }

    $payload = [
      'token' => $token,
      'from' => $from,
      'to' => $phone,
      'message' => $message,
    ];

    $response = Http::timeout(30)
      ->asForm()
      ->post($url, $payload);

    if (! $response->successful() && $response->status() === 405) {
      $response = Http::timeout(30)->get($url, $payload);
    }

    $raw = trim($response->body());

    return $this->parseResponse($raw, $response->status());
  }

  /**
   * Récupère le solde SMS restant sur le compte Kecel.
   *
   * @return array{balance: string|null, raw: string, error: string|null, source: string} Solde, réponse brute, erreur et source
   */
  public function balance(): array
  {
    $token = (string) config('sms.token');
    $from = (string) config('sms.from');
    $url = (string) config('sms.balance_url');

    if ($token === '') {
      return $this->resolveBalanceResult([
        'balance' => null,
        'raw' => 'SMS_TOKEN manquant',
        'error' => 'SMS_TOKEN manquant',
        'source' => 'api',
      ]);
    }

    $raw = $this->fetchBalanceRaw($url, $token, $from);
    $balance = $this->extractBalance($raw);
    $error = $balance === null ? $this->extractBalanceError($raw) : null;

    return $this->resolveBalanceResult([
      'balance' => $balance,
      'raw' => $raw,
      'error' => $error,
      'source' => 'api',
    ]);
  }

  /**
   * Indique si le pilote Kecel est actif.
   *
   * @return bool True si l'envoi API est activé
   */
  public function isEnabled(): bool
  {
    return config('sms.driver') === 'keccel'
      && filled(config('sms.token'))
      && filled(config('sms.from'));
  }

  /**
   * Analyse la réponse texte ou JSON de l'API Kecel.
   *
   * @param string $raw Réponse brute
   * @param int $httpStatus Code HTTP
   * @return KecelSmsResult Résultat interprété
   */
  private function parseResponse(string $raw, int $httpStatus): KecelSmsResult
  {
    if ($httpStatus >= 400) {
      return new KecelSmsResult(
        success: false,
        rawResponse: $raw,
        reference: null,
        message: $raw !== '' ? $raw : 'HTTP '.$httpStatus,
      );
    }

    if ($raw === '') {
      return new KecelSmsResult(
        success: true,
        rawResponse: $raw,
        reference: null,
        message: 'HTTP '.$httpStatus,
      );
    }

    $decoded = json_decode($raw, true);

    if (is_array($decoded)) {
      $status = strtoupper((string) ($decoded['status'] ?? $decoded['code'] ?? ''));
      $reference = isset($decoded['reference']) ? (string) $decoded['reference'] : null;
      $message = isset($decoded['message']) ? (string) $decoded['message'] : $raw;
      $success = in_array($status, ['ACCEPTED', 'OK', 'SUCCESS', 'SENT', '0'], true)
        || ($status !== '' && ! $this->looksLikeFailure($raw));

      return new KecelSmsResult(
        success: $success,
        rawResponse: $raw,
        reference: $reference,
        message: $message,
      );
    }

    $parts = array_map('trim', explode(',', $raw));
    $status = strtoupper($parts[0] ?? '');
    $reference = $parts[1] ?? null;
    $message = $parts[2] ?? $raw;

    $success = in_array($status, ['ACCEPTED', 'OK', 'SUCCESS', 'SENT', 'DELIVERED'], true)
      || $status === '0'
      || (preg_match('/^\d{4,}$/', $status) === 1 && ! $this->looksLikeFailure($raw))
      || (! $this->looksLikeFailure($raw) && $httpStatus >= 200 && $httpStatus < 300);

    return new KecelSmsResult(
      success: $success,
      rawResponse: $raw,
      reference: $reference,
      message: $message,
    );
  }

  /**
   * Détecte une réponse d'échec explicite renvoyée par Kecel.
   *
   * @param string $raw Réponse brute
   * @return bool True si la réponse indique un échec
   */
  private function looksLikeFailure(string $raw): bool
  {
    return preg_match('/\b(error|fail|failed|invalid|rejected|refused|denied|insufficient|unauthorized)\b/i', $raw) === 1;
  }

  /**
   * Extrait le solde SMS depuis la réponse Kecel.
   *
   * @param string $raw Réponse brute
   * @return string|null Solde formaté ou null
   */
  private function extractBalance(string $raw): ?string
  {
    if ($raw === '') {
      return null;
    }

    $decoded = json_decode($raw, true);

    if (is_array($decoded)) {
      $balance = $decoded['balance'] ?? null;

      if (is_numeric($balance) || (is_string($balance) && $balance !== '' && is_numeric(trim($balance)))) {
        return (string) $balance;
      }

      return null;
    }

    if (preg_match('/\d+/', $raw, $matches) === 1) {
      return $matches[0];
    }

    return null;
  }

  /**
   * Extrait un message d'erreur depuis la réponse solde Kecel.
   *
   * @param string $raw Réponse brute
   * @return string|null Message d'erreur ou null
   */
  private function extractBalanceError(string $raw): ?string
  {
    $decoded = json_decode($raw, true);

    if (! is_array($decoded)) {
      return null;
    }

    $status = $decoded['status'] ?? null;

    if (is_string($status) && $status !== '') {
      return $status;
    }

    return null;
  }

  /**
   * Interroge l'API Kecel avec plusieurs formats de paramètres connus.
   *
   * @param string $url URL de consultation du solde
   * @param string $token Jeton API
   * @param string $from Expéditeur SMS
   * @return string Réponse brute de l'API
   */
  private function fetchBalanceRaw(string $url, string $token, string $from): string
  {
    $attempts = [
      ['token' => $token, 'from' => $from],
      ['token' => $token, 'FROM' => $from],
      ['token' => $token],
    ];

    foreach ($attempts as $params) {
      $response = Http::timeout(20)->get($url, array_filter($params));
      $raw = trim($response->body());
      $balance = $this->extractBalance($raw);

      if ($balance !== null) {
        return $raw;
      }
    }

    $postResponse = Http::timeout(20)
      ->asForm()
      ->post($url, array_filter([
        'token' => $token,
        'from' => $from !== '' ? $from : null,
        'FROM' => $from !== '' ? $from : null,
      ]));

    return trim($postResponse->body());
  }

  /**
   * Applique le solde manuel si l'API Kecel ne renvoie pas de valeur.
   *
   * @param array{balance: string|null, raw: string, error: string|null, source: string} $result Résultat API
   * @return array{balance: string|null, raw: string, error: string|null, source: string} Résultat final
   */
  private function resolveBalanceResult(array $result): array
  {
    if ($result['balance'] !== null) {
      return $result;
    }

    $manualBalance = AdminAppearanceSetting::instance()->sms_manual_balance;

    if ($manualBalance === null) {
      return $result;
    }

    return [
      'balance' => (string) $manualBalance,
      'raw' => $result['raw'],
      'error' => null,
      'source' => 'manual',
    ];
  }
}
