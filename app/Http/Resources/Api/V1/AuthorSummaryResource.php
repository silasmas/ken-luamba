<?php

namespace App\Http\Resources\Api\V1;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API résumée pour un auteur.
 */
class AuthorSummaryResource extends JsonResource
{
  /**
   * Transforme l'auteur en tableau JSON résumé.
   *
   * @param Request $request Requête HTTP entrante
   * @return array<string, mixed> Données sérialisées
   */
  public function toArray(Request $request): array
  {
    return [
      'id' => $this->id,
      'fullName' => $this->full_name,
      'slug' => $this->slug,
      'title' => $this->title,
      'shortBio' => $this->short_bio,
      'profileImage' => MediaUrl::fromPath($this->profile_image),
    ];
  }
}
