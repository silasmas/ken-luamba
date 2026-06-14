<?php

namespace App\Services\Sms;

use App\Models\AdminAppearanceSetting;
use Illuminate\Support\Facades\Http;
use RuntimeException;

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

    $response = Http::timeout(30)
      ->get($url, [
        'token' => $token,
        'from' => $from,
        'to' => $phone,
        'message' => $message,
      ]);

    $raw = trim($response->body());

    return $this->parseResponse($raw);
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
   * Analyse la réponse texte de l'API Kecel.
   *
   * @param string $raw Réponse brute
   * @return KecelSmsResult Résultat interprété
   */
  private function parseResponse(string $raw): KecelSmsResult
  {
    $parts = array_map('trim', explode(',', $raw));
    $status = strtoupper($parts[0] ?? '');
    $reference = $parts[1] ?? null;
    $message = $parts[2] ?? null;

    return new KecelSmsResult(
      success: in_array($status, ['ACCEPTED', 'OK', 'SUCCESS'], true),
      rawResponse: $raw,
      reference: $reference,
      message: $message,
    );
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
