<?php

namespace App\Http\Resources\Api\V1;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour un utilisateur connecté.
 */
class UserResource extends JsonResource
{
  /**
   * Transforme l'utilisateur en tableau JSON.
   *
   * @param Request $request Requête HTTP entrante
   * @return array<string, mixed> Données sérialisées
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'fullName' => $this->full_name ?? $this->name,
      'email' => $this->email,
      'phone' => $this->phone,
      'avatarUrl' => MediaUrl::fromPath($this->avatar_path),
      'profileAddress' => $this->profile_address,
      'deliveryAddress' => $this->delivery_address,
      'role' => $this->role->value,
      'roleLabel' => $this->role->label(),
    ];
  }
}
