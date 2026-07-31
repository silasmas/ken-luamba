<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\PaymentChannel;
use App\Http\Controllers\Controller;
use App\Services\DirectPaymentQrService;
use App\Services\DirectPaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

/**
 * Contrôleur API public du parcours paiement direct.
 */
class DirectPaymentController extends Controller
{
  /**
   * Initialise le contrôleur.
   *
   * @param DirectPaymentService $directPaymentService Service paiement direct
   * @param DirectPaymentQrService $directPaymentQrService Générateur QR du lien public
   */
  public function __construct(
    private readonly DirectPaymentService $directPaymentService,
    private readonly DirectPaymentQrService $directPaymentQrService,
  ) {}

  /**
   * Retourne le catalogue et le pack sélectionné par défaut.
   *
   * @return JsonResponse Catalogue public
   */
  public function catalog(): JsonResponse
  {
    return response()->json([
      'data' => $this->directPaymentService->catalog(),
    ]);
  }

  /**
   * Retourne le QR code PNG du lien page paiement direct.
   *
   * @param Request $request Requête (size optionnel)
   * @return Response Image PNG
   */
  public function qrImage(Request $request): Response
  {
    $size = (int) $request->integer('size', 400);
    $png = $this->directPaymentQrService->generatePng($size);
    $disposition = $request->boolean('download') ? 'attachment' : 'inline';

    return response($png, 200, [
      'Content-Type' => 'image/png',
      'Content-Disposition' => $disposition.'; filename="paiement-direct-qr.png"',
      'Cache-Control' => 'public, max-age=300',
    ]);
  }

  /**
   * Crée la commande guest et lance le paiement.
   *
   * @param Request $request Payload checkout
   * @return JsonResponse Résultat initiation
   */
  public function checkout(Request $request): JsonResponse
  {
    $payload = $request->validate([
      'email' => ['required', 'email', 'max:255'],
      'bookFormatIds' => ['required', 'array', 'min:1'],
      'bookFormatIds.*' => ['required', 'uuid'],
      'channel' => ['required', Rule::enum(PaymentChannel::class)],
      'providerCode' => ['required_if:channel,mobile_money', 'nullable', 'string', 'max:32'],
      'phone' => ['required_if:channel,mobile_money', 'nullable', 'regex:/^243[0-9]{9}$/'],
    ]);

    $result = $this->directPaymentService->checkout($payload);

    return response()->json([
      'message' => 'Commande créée. Suivez les instructions de paiement.',
      'data' => $result,
    ], 201);
  }

  /**
   * Retourne l'état public d'une commande paiement direct.
   *
   * @param string $publicToken Token opaque de la commande
   * @return JsonResponse Détail résultat
   */
  public function result(string $publicToken): JsonResponse
  {
    return response()->json([
      'data' => $this->directPaymentService->result($publicToken),
    ]);
  }
}
