<?php

namespace App\Services;

use App\Models\DirectPaymentSetting;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;

/**
 * Génère le QR code PNG du lien public paiement direct.
 */
class DirectPaymentQrService
{
  /**
   * Génère le PNG du QR pointant vers la page paiement direct.
   *
   * @param int $size Taille du QR en pixels
   * @return string Contenu binaire PNG
   */
  public function generatePng(int $size = 400): string
  {
    $url = DirectPaymentSetting::instance()->publicUrl();

    $result = (new Builder(
      writer: new PngWriter(),
      writerOptions: [],
      validateResult: false,
      data: $url,
      encoding: new Encoding('UTF-8'),
      errorCorrectionLevel: ErrorCorrectionLevel::Medium,
      size: max(120, min($size, 1200)),
      margin: 16,
      roundBlockSizeMode: RoundBlockSizeMode::Margin,
    ))->build();

    return $result->getString();
  }

  /**
   * URL publique de l'image QR (téléchargeable / affichable).
   *
   * @return string URL absolue
   */
  public function imageUrl(): string
  {
    return url('/api/v1/direct-payment/qr.png');
  }
}
