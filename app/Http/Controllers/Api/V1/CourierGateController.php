<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\CourierGateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur API de la porte livreur (code + session 24h).
 */
class CourierGateController extends Controller
{
  /**
   * Initialise le contrôleur.
   *
   * @param CourierGateService $courierGateService Service porte livreur
   */
  public function __construct(
    private readonly CourierGateService $courierGateService,
  ) {}

  /**
   * Ouvre une session livreur via code.
   *
   * @param Request $request Requête avec code
   * @return JsonResponse Token Sanctum 24h
   */
  public function login(Request $request): JsonResponse
  {
    $payload = $request->validate([
      'code' => ['required', 'string', 'max:64'],
    ]);

    $result = $this->courierGateService->login($payload['code']);

    return response()->json([
      'message' => 'Session livreur ouverte pour 24 heures.',
      'data' => $result,
    ]);
  }

  /**
   * Ferme la session livreur courante.
   *
   * @param Request $request Requête authentifiée
   * @return JsonResponse Confirmation
   */
  public function logout(Request $request): JsonResponse
  {
    $this->courierGateService->logout($request->user());

    return response()->json([
      'message' => 'Session livreur fermée.',
    ]);
  }

  /**
   * Retourne le profil de la session livreur.
   *
   * @param Request $request Requête authentifiée
   * @return JsonResponse Profil
   */
  public function me(Request $request): JsonResponse
  {
    return response()->json([
      'data' => $this->courierGateService->me($request->user()),
    ]);
  }

  /**
   * Scanne un QR code commande.
   *
   * @param Request $request Requête avec token QR
   * @return JsonResponse Infos commande
   */
  public function scan(Request $request): JsonResponse
  {
    $payload = $request->validate([
      'token' => ['required', 'string'],
    ]);

    return response()->json([
      'data' => $this->courierGateService->scan($payload['token']),
    ]);
  }

  /**
   * Confirme la remise après scan.
   *
   * @param Request $request Token QR + photo optionnelle
   * @return JsonResponse Résultat
   */
  public function confirm(Request $request): JsonResponse
  {
    $request->validate([
      'token' => ['required', 'string'],
      'comment' => ['nullable', 'string', 'max:500'],
      'photo' => ['nullable', 'image', 'max:5120'],
    ]);

    $result = $this->courierGateService->confirm(
      $request->user(),
      $request->string('token')->toString(),
      $request->file('photo'),
      $request->input('comment'),
    );

    return response()->json([
      'message' => $result['message'],
      'data' => $result,
    ]);
  }
}
