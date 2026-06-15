<?php

namespace App\Http\Resources\Api\V1;

use App\Support\MediaUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ressource API pour un auteur.
 */
class AuthorResource extends JsonResource
{
  /**
   * Transforme l'auteur en tableau JSON.
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
      'roles' => $this->roles ?? ($this->title ? [$this->title] : []),
      'shortBio' => $this->short_bio,
      'fullBio' => $this->full_bio,
      'profileImage' => MediaUrl::fromPath($this->profile_image),
      'coverImage' => MediaUrl::fromPath($this->cover_image),
      'homeHeroPrimaryImage' => MediaUrl::fromPath($this->home_hero_primary_image),
      'homeHeroOverlayImage' => MediaUrl::fromPath($this->home_hero_overlay_image),
      'homeSectionPrimaryImage' => MediaUrl::fromPath($this->home_section_primary_image),
      'homeSectionOverlayImage' => MediaUrl::fromPath($this->home_section_overlay_image),
      'pagePrimaryImage' => MediaUrl::fromPath($this->page_primary_image),
      'pageOverlayImage' => MediaUrl::fromPath($this->page_overlay_image),
      'socialLinks' => $this->social_links ?? [],
      'featuredQuote' => $this->featured_quote,
      'isPrimary' => $this->is_primary,
      'metaTitle' => $this->meta_title,
      'metaDescription' => $this->meta_description,
      'books' => BookSummaryResource::collection($this->whenLoaded('books')),
    ];
  }
}
