<?php

namespace App\Services;

use App\Enums\OtpType;
use App\Enums\UserRole;
use App\Models\OtpCode;
use App\Models\User;
use App\Notifications\OtpCodeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Service de génération, envoi et validation des codes OTP.
 */
class OtpService
{
  /**
   * Envoie un OTP pour une inscription.
   *
   * @param string $email Adresse email du futur client
   * @param string $fullName Nom complet saisi à l'inscription
   * @param string $phone Téléphone MSISDN 243XXXXXXXXX
   * @return void
   */
  public function sendRegisterOtp(string $email, string $fullName, string $phone): void
  {
    if (User::query()->where('email', $email)->exists()) {
      throw ValidationException::withMessages([
        'email' => ['Un compte existe déjà avec cet email.'],
      ]);
    }

    $this->sendOtp($email, OtpType::Register, null, $fullName, $phone);
  }

  /**
   * Envoie un OTP pour une connexion.
   *
   * @param string $email Adresse email du client
   * @return void
   */
  public function sendLoginOtp(string $email): void
  {
    $user = User::query()->where('email', $email)->first();

    if ($user === null) {
      throw ValidationException::withMessages([
        'email' => ['Aucun compte trouvé avec cet email.'],
      ]);
    }

    if (! $user->is_active) {
      throw ValidationException::withMessages([
        'email' => ['Ce compte est désactivé.'],
      ]);
    }

    $this->sendOtp($email, OtpType::Login, $user);
  }

  /**
   * Vérifie un code OTP et retourne l'utilisateur authentifié.
   *
   * @param string $email Adresse email
   * @param string $code Code OTP saisi
   * @param OtpType $type Type d'OTP attendu
   * @return User Utilisateur créé ou connecté
   */
  public function verify(string $email, string $code, OtpType $type): User
  {
    $otpCode = OtpCode::query()
      ->where('email', $email)
      ->where('type', $type)
      ->whereNull('used_at')
      ->latest()
      ->first();

    if ($otpCode === null || ! $otpCode->isValid()) {
      throw ValidationException::withMessages([
        'code' => ['Code invalide ou expiré.'],
      ]);
    }

    if (! Hash::check($code, $otpCode->code)) {
      $otpCode->increment('attempts');

      throw ValidationException::withMessages([
        'code' => ['Code incorrect.'],
      ]);
    }

    $otpCode->update(['used_at' => now()]);

    if ($type === OtpType::Register) {
      return $this->createClientFromOtp($otpCode);
    }

    $user = User::query()->where('email', $email)->firstOrFail();
    $user->forceFill(['email_verified_at' => now()])->save();

    return $user;
  }

  /**
   * Génère et envoie un code OTP par email.
   *
   * @param string $email Adresse email cible
   * @param OtpType $type Type d'OTP
   * @param User|null $user Utilisateur existant pour la connexion
   * @param string|null $fullName Nom complet pour l'inscription
   * @param string|null $phone Téléphone pour l'inscription
   * @return void
   */
  private function sendOtp(
    string $email,
    OtpType $type,
    ?User $user = null,
    ?string $fullName = null,
    ?string $phone = null,
  ): void {
    $this->ensureRateLimitNotExceeded($email, $type);

    OtpCode::query()
      ->where('email', $email)
      ->where('type', $type)
      ->whereNull('used_at')
      ->delete();

    $plainCode = $this->generatePlainCode();

    OtpCode::query()->create([
      'user_id' => $user?->id,
      'email' => $email,
      'full_name' => $fullName,
      'phone' => $phone,
      'code' => Hash::make($plainCode),
      'type' => $type,
      'expires_at' => now()->addMinutes(config('otp.expiry_minutes', 10)),
    ]);

    Notification::route('mail', $email)
      ->notify(new OtpCodeNotification($plainCode, $type));
  }

  /**
   * Crée un compte client après validation OTP d'inscription.
   *
   * @param OtpCode $otpCode Enregistrement OTP validé
   * @return User Nouveau compte client
   */
  private function createClientFromOtp(OtpCode $otpCode): User
  {
    return User::query()->create([
      'name' => $otpCode->full_name ?? 'Client',
      'full_name' => $otpCode->full_name,
      'email' => $otpCode->email,
      'phone' => $otpCode->phone,
      'password' => Hash::make(Str::random(32)),
      'role' => UserRole::Client,
      'is_active' => true,
      'email_verified_at' => now(),
    ]);
  }

  /**
   * Vérifie que la limite horaire d'envoi OTP n'est pas dépassée.
   *
   * @param string $email Adresse email cible
   * @param OtpType $type Type d'OTP (connexion ou inscription)
   * @return void
   */
  private function ensureRateLimitNotExceeded(string $email, OtpType $type): void
  {
    if (config('otp.disable_rate_limit') || app()->environment('local')) {
      return;
    }

    $maxRequests = (int) config('otp.max_requests_per_hour', 20);

    if ($maxRequests <= 0) {
      return;
    }

    $recentCount = OtpCode::query()
      ->where('email', $email)
      ->where('type', $type)
      ->where('created_at', '>=', now()->subHour())
      ->count();

    if ($recentCount >= $maxRequests) {
      throw ValidationException::withMessages([
        'email' => ['Trop de demandes. Réessayez dans quelques minutes.'],
      ]);
    }
  }

  /**
   * Génère un code OTP numérique.
   *
   * @return string Code en clair
   */
  private function generatePlainCode(): string
  {
    $length = config('otp.code_length', 6);

    return str_pad((string) random_int(0, (10 ** $length) - 1), $length, '0', STR_PAD_LEFT);
  }
}
