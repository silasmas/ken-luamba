<?php

namespace App\Services;

use App\Models\Book;
use App\Models\User;
use App\Models\WishlistItem;
use Illuminate\Support\Collection;

/**
 * Gère la liste de favoris des utilisateurs connectés.
 */
class WishlistService
{
  /**
   * Retourne les slugs des livres favoris d'un utilisateur.
   *
   * @param User $user Utilisateur connecté
   * @return Collection<int, string> Slugs des livres
   */
  public function bookSlugsFor(User $user): Collection
  {
    return WishlistItem::query()
      ->where('user_id', $user->id)
      ->with('book:id,slug')
      ->get()
      ->map(fn (WishlistItem $item): string => (string) $item->book?->slug)
      ->filter()
      ->values();
  }

  /**
   * Ajoute un livre aux favoris.
   *
   * @param User $user Utilisateur connecté
   * @param Book $book Livre à favoriser
   * @return bool True si ajouté, false si déjà présent
   */
  public function add(User $user, Book $book): bool
  {
    $item = WishlistItem::query()->firstOrCreate([
      'user_id' => $user->id,
      'book_id' => $book->id,
    ]);

    return $item->wasRecentlyCreated;
  }

  /**
   * Retire un livre des favoris.
   *
   * @param User $user Utilisateur connecté
   * @param Book $book Livre à retirer
   * @return bool True si retiré, false si absent
   */
  public function remove(User $user, Book $book): bool
  {
    return WishlistItem::query()
      ->where('user_id', $user->id)
      ->where('book_id', $book->id)
      ->delete() > 0;
  }

  /**
   * Bascule un livre dans les favoris.
   *
   * @param User $user Utilisateur connecté
   * @param Book $book Livre cible
   * @return bool True si le livre est maintenant en favori
   */
  public function toggle(User $user, Book $book): bool
  {
    $exists = WishlistItem::query()
      ->where('user_id', $user->id)
      ->where('book_id', $book->id)
      ->exists();

    if ($exists) {
      $this->remove($user, $book);

      return false;
    }

    $this->add($user, $book);

    return true;
  }

  /**
   * Retourne les livres favoris avec leurs métadonnées catalogue.
   *
   * @param User $user Utilisateur connecté
   * @return Collection<int, Book> Livres favoris
   */
  public function booksFor(User $user): Collection
  {
    return WishlistItem::query()
      ->where('user_id', $user->id)
      ->with(['book.author', 'book.formats.pricingPeriods'])
      ->latest('created_at')
      ->get()
      ->map(fn (WishlistItem $item): ?Book => $item->book)
      ->filter()
      ->values();
  }

  /**
   * Indique si un livre est en favori.
   *
   * @param User $user Utilisateur connecté
   * @param string $bookSlug Slug du livre
   * @return bool True si favori
   */
  public function contains(User $user, string $bookSlug): bool
  {
    return WishlistItem::query()
      ->where('user_id', $user->id)
      ->whereHas('book', fn ($query) => $query->where('slug', $bookSlug))
      ->exists();
  }
}
