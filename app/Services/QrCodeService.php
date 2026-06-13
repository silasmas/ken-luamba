<?php

namespace App\Services;

use App\Models\Order;
use App\Models\QrCode;
use Illuminate\Support\Str;

/**
 * Service de génération et validation des QR codes commande.
 */
class QrCodeService
{
  /**
   * Génère un QR code pour une commande payée.
   *
   * @param Order $order Commande payée
   * @return QrCode Enregistrement QR créé
   */
  public function generateForOrder(Order $order): QrCode
  {
    return QrCode::query()->updateOrCreate(
      ['order_id' => $order->id],
      [
        'token' => hash('sha256', $order->id.Str::uuid()),
        'is_used' => false,
        'used_at' => null,
      ],
    );
  }
}
