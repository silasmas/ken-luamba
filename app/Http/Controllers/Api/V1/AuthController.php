<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OtpType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Auth\VerifyOtpRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Services\OtpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Contrôleur API pour l'authentification OTP.
 */
class AuthController extends Controller
{
  /**
   * Initialise le contrôleur avec le service OTP.
   *
   * @param OtpService $otpService Service de gestion des codes OTP
   */
  public function __construct(
    private readonly OtpService $otpService,
  ) {}

  /**
   * Demande un code OTP pour une inscription.
   *
   * @param RegisterRequest $request Données validées
   * @return JsonResponse Confirmation d'envoi
   */
  public function register(RegisterRequest $request): JsonResponse
  {
    $this->otpService->sendRegisterOtp(
      $request->validated('email'),
      $request->validated('fullName'),
    );

    return response()->json([
      'message' => 'Code OTP envoyé par email.',
    ]);
  }

  /**
   * Demande un code OTP pour une connexion.
   *
   * @param LoginRequest $request Données validées
   * @return JsonResponse Confirmation d'envoi
   */
  public function login(LoginRequest $request): JsonResponse
  {
    $this->otpService->sendLoginOtp($request->validated('email'));

    return response()->json([
      'message' => 'Code OTP envoyé par email.',
    ]);
  }

  /**
   * Vérifie un code OTP et retourne un token Sanctum.
   *
   * @param VerifyOtpRequest $request Données validées
   * @return JsonResponse Token et profil utilisateur
   */
  public function verifyOtp(VerifyOtpRequest $request): JsonResponse
  {
    $user = $this->otpService->verify(
      $request->validated('email'),
      $request->validated('code'),
      OtpType::from($request->validated('type')),
    );

    app(\App\Services\CartService::class)->mergeGuestCart(
      $user,
      $request->header('X-Cart-Session'),
    );

    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
      'message' => 'Authentification réussie.',
      'token' => $token,
      'user' => new UserResource($user),
    ]);
  }

  /**
   * Révoque le token courant de l'utilisateur.
   *
   * @param Request $request Requête authentifiée
   * @return JsonResponse Confirmation de déconnexion
   */
  public function logout(Request $request): JsonResponse
  {
    $request->user()?->currentAccessToken()?->delete();

    return response()->json([
      'message' => 'Déconnexion réussie.',
    ]);
  }

  /**
   * Retourne le profil de l'utilisateur connecté.
   *
   * @param Request $request Requête authentifiée
   * @return UserResource Profil utilisateur
   */
  public function me(Request $request): JsonResponse
  {
    return response()->json([
      'data' => new UserResource($request->user()),
    ]);
  }

  /**
   * Met à jour le profil de l'utilisateur connecté.
   *
   * @param UpdateProfileRequest $request Données validées
   * @return JsonResponse Profil mis à jour
   */
  public function updateProfile(UpdateProfileRequest $request): JsonResponse
  {
    $user = $request->user();
    $data = $request->validated();

    if (isset($data['fullName'])) {
      $user->full_name = $data['fullName'];
      $user->name = $data['fullName'];
    }

    if (array_key_exists('phone', $data)) {
      $user->phone = $data['phone'];
    }

    if (array_key_exists('profileAddress', $data)) {
      $user->profile_address = $data['profileAddress'];
    }

    if (array_key_exists('deliveryAddress', $data)) {
      $user->delivery_address = $data['deliveryAddress'];
    }

    $user->save();

    return response()->json([
      'message' => 'Profil mis à jour.',
      'data' => new UserResource($user),
    ]);
  }

  /**
   * Met à jour la photo de profil.
   *
   * @param Request $request Requête avec fichier avatar
   * @return JsonResponse Profil mis à jour
   */
  public function updateAvatar(Request $request): JsonResponse
  {
    $request->validate([
      'avatar' => ['required', 'image', 'max:2048'],
    ]);

    $user = $request->user();

    if ($user->avatar_path !== null) {
      Storage::disk('public')->delete($user->avatar_path);
    }

    $path = $request->file('avatar')->store('avatars', 'public');
    $user->update(['avatar_path' => $path]);

    return response()->json([
      'message' => 'Photo de profil mise à jour.',
      'data' => new UserResource($user->fresh()),
    ]);
  }
}
