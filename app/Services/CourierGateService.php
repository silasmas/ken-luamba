<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Accès livreur par code (session Sanctum 24h) sans login classique.
 */
class CourierGateService
{
  /**
   * Initialise le service porte livreur.
   *
   * @param DeliveryService $deliveryService Service livraison / scan QR
   */
  public function __construct(
    private readonly DeliveryService $deliveryService,
  ) {}

  /**
   * Authentifie un livreur via son code et émet un token Sanctum 24h.
   *
   * @param string $code Code livreur en clair
   * @return array{token: string, expiresAt: string, courier: array<string, mixed>} Session créée
   */
  public function login(string $code): array
  {
    $code = trim($code);

    if ($code === '') {
      throw ValidationException::withMessages([
        'code' => ['Code livreur requis.'],
      ]);
    }

    $couriers = User::query()
      ->where('role', UserRole::Courier)
      ->where('is_active', true)
      ->whereNotNull('courier_code_hash')
      ->get();

    $matched = $couriers->first(
      fn (User $courier): bool => Hash::check($code, (string) $courier->courier_code_hash),
    );

    if ($matched === null) {
      throw ValidationException::withMessages([
        'code' => ['Code livreur invalide.'],
      ]);
    }

    $matched->tokens()->where('name', 'courier-gate')->delete();

    $expiresAt = now()->addHours(24);
    $accessToken = $matched->createToken('courier-gate', ['courier-gate'], $expiresAt);

    return [
      'token' => $accessToken->plainTextToken,
      'expiresAt' => $expiresAt->toIso8601String(),
      'courier' => $this->formatCourier($matched),
    ];
  }

  /**
   * Révoque le token porte livreur courant.
   *
   * @param User $courier Livreur authentifié
   */
  public function logout(User $courier): void
  {
    $token = $courier->currentAccessToken();

    if ($token instanceof PersonalAccessToken) {
      $token->delete();
    }
  }

  /**
   * Retourne le profil session du livreur.
   *
   * @param User $courier Livreur authentifié
   * @return array<string, mixed> Infos session
   */
  public function me(User $courier): array
  {
    $expiresAt = $courier->currentAccessToken()?->expires_at;

    return [
      'courier' => $this->formatCourier($courier),
      'expiresAt' => $expiresAt?->toIso8601String(),
    ];
  }

  /**
   * Scanne un QR code commande.
   *
   * @param string $token Token QR
   * @return array<string, mixed> Infos commande
   */
  public function scan(string $token): array
  {
    return $this->deliveryService->scanQrToken($token);
  }

  /**
   * Confirme la remise après scan QR.
   *
   * @param User $courier Livreur authentifié
   * @param string $token Token QR
   * @param UploadedFile|null $photo Preuve photo optionnelle
   * @param string|null $comment Commentaire optionnel
   * @return array<string, mixed> Résultat confirmation
   */
  public function confirm(
    User $courier,
    string $token,
    ?UploadedFile $photo = null,
    ?string $comment = null,
  ): array {
    return $this->deliveryService->confirmByQr($courier, $token, $photo, $comment);
  }

  /**
   * Génère un code livreur aléatoire et persiste son hash.
   *
   * @param User $courier Utilisateur livreur
   * @return string Code en clair (affichage one-shot)
   */
  public static function generateAndStoreCode(User $courier): string
  {
    $plain = Str::upper(Str::random(8));
    $courier->forceFill([
      'courier_code_hash' => Hash::make($plain),
    ])->save();

    return $plain;
  }

  /**
   * Formate le profil livreur pour l'API.
   *
   * @param User $courier Livreur
   * @return array<string, mixed> Payload public
   */
  private function formatCourier(User $courier): array
  {
    return [
      'id' => $courier->id,
      'fullName' => $courier->full_name,
      'email' => $courier->email,
    ];
  }
}
