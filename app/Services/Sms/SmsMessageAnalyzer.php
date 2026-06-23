<?php

namespace App\Services\Sms;

use Illuminate\Support\HtmlString;

/**
 * Analyse la longueur et le découpage SMS (GSM-7 vs Unicode).
 */
class SmsMessageAnalyzer
{
  public const GSM_SINGLE = 160;

  public const GSM_MULTI = 153;

  public const UCS2_SINGLE = 70;

  public const UCS2_MULTI = 67;

  /**
   * Caractères compatibles encodage GSM-7 (simplifié).
   */
  private const GSM_BASIC = "@£\$¥èéùìòÇ\nØø\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";

  /**
   * Analyse un texte SMS.
   *
   * @param string $message Contenu du SMS
   * @return array{encoding: string, charCount: int, segments: int, singleLimit: int, multiLimit: int, encodingLabel: string, warning: string|null} Statistiques
   */
  public function analyze(string $message): array
  {
    $encoding = $this->detectEncoding($message);
    $length = mb_strlen($message, 'UTF-8');
    $singleLimit = $encoding === 'GSM-7' ? self::GSM_SINGLE : self::UCS2_SINGLE;
    $multiLimit = $encoding === 'GSM-7' ? self::GSM_MULTI : self::UCS2_MULTI;
    $segments = $length <= $singleLimit ? 1 : (int) ceil($length / $multiLimit);
    $encodingLabel = $encoding === 'GSM-7'
      ? 'GSM-7 (lettres latines sans accents)'
      : 'Unicode (accents, emojis, caractères spéciaux)';

    $warning = null;

    if ($segments > 3) {
      $warning = "Message long : {$segments} SMS seront débités par destinataire.";
    } elseif ($segments > 1) {
      $warning = "Ce message sera découpé en {$segments} SMS par destinataire.";
    }

    if ($encoding === 'UCS-2') {
      $warning = trim(($warning ?? '').' Les accents réduisent la limite à 70 caractères par SMS.');
    }

    return [
      'encoding' => $encoding,
      'charCount' => $length,
      'segments' => $segments,
      'singleLimit' => $singleLimit,
      'multiLimit' => $multiLimit,
      'encodingLabel' => $encodingLabel,
      'warning' => $warning !== '' ? $warning : null,
    ];
  }

  /**
   * Formate un résumé lisible pour l'admin.
   *
   * @param array<string, mixed> $analysis Résultat de analyze()
   * @return string Résumé texte
   */
  public function formatSummary(array $analysis): string
  {
    $parts = [
      $analysis['charCount'].' caractère(s)',
      $analysis['segments'].' SMS',
      $analysis['encodingLabel'],
      'limite 1 SMS : '.$analysis['singleLimit'].' car.',
    ];

    if ($analysis['segments'] > 1) {
      $parts[] = 'par segment : '.$analysis['multiLimit'].' car.';
    }

    $summary = implode(' · ', $parts);

    if (filled($analysis['warning'] ?? null)) {
      $summary .= "\n".$analysis['warning'];
    }

    return $summary;
  }

  /**
   * Affiche l'aperçu SMS dans l'admin Filament.
   *
   * @param string $message Contenu rendu
   * @param array<string, mixed> $analysis Résultat de analyze()
   * @return HtmlString Markup de l'aperçu
   */
  public function formatPreviewHtml(string $message, array $analysis): HtmlString
  {
    $summary = e($this->formatSummary($analysis));
    $body = e($message !== '' ? $message : '(Message vide)');

    return new HtmlString(
      '<div class="space-y-2">'
      .'<p class="text-xs font-medium text-gray-600 dark:text-gray-300">'.$summary.'</p>'
      .'<pre class="max-h-64 overflow-auto whitespace-pre-wrap rounded-lg border border-gray-200 bg-gray-50 p-3 text-xs text-gray-900 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100">'.$body.'</pre>'
      .'</div>',
    );
  }

  /**
   * Détecte si le message tient en GSM-7 ou nécessite Unicode.
   *
   * @param string $message Contenu du SMS
   * @return string GSM-7 ou UCS-2
   */
  private function detectEncoding(string $message): string
  {
    $length = mb_strlen($message, 'UTF-8');

    for ($index = 0; $index < $length; $index++) {
      $char = mb_substr($message, $index, 1, 'UTF-8');

      if (! str_contains(self::GSM_BASIC, $char)) {
        return 'UCS-2';
      }
    }

    return 'GSM-7';
  }
}
