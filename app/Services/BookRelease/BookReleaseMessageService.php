<?php

namespace App\Services\BookRelease;

use App\Models\Book;
use App\Models\BookReleaseSubscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Gère les modèles de messages pour les alertes sortie livre.
 */
class BookReleaseMessageService
{
  /**
   * Variables disponibles dans les modèles d'alerte sortie.
   */
  public const PLACEHOLDER_HINT = '{subscriber_email}, {book_title}, {book_subtitle}, {release_date}, {release_date_short}, {book_link}';

  /**
   * Retourne la définition de chaque variable.
   *
   * @return array<string, string> Variables et description
   */
  public static function placeholderDefinitions(): array
  {
    return [
      '{subscriber_email}' => 'Adresse e-mail de l\'inscrit.',
      '{book_title}' => 'Titre du livre.',
      '{book_subtitle}' => 'Sous-titre du livre.',
      '{release_date}' => 'Date de sortie au format long.',
      '{release_date_short}' => 'Date de sortie au format court.',
      '{book_link}' => 'Lien vers la fiche livre sur le site.',
    ];
  }

  /**
   * Normalise les modèles stockés sur un livre.
   *
   * @param mixed $messages Modèles bruts
   * @return list<array<string, mixed>> Modèles normalisés
   */
  public function normalizeStoredMessages(mixed $messages): array
  {
    if (! is_array($messages)) {
      return $this->defaultMessages();
    }

    $normalized = [];

    foreach ($messages as $message) {
      if (! is_array($message)) {
        continue;
      }

      $body = trim((string) ($message['body'] ?? ''));

      if ($body === '') {
        continue;
      }

      $normalized[] = [
        'id' => self::resolveMessageId($message, $body, count($normalized)),
        'label' => trim((string) ($message['label'] ?? 'Message')) ?: 'Message',
        'email_subject' => trim((string) ($message['email_subject'] ?? 'Votre livre est disponible')),
        'body' => $body,
      ];
    }

    return $normalized !== [] ? $normalized : $this->defaultMessages();
  }

  /**
   * Modèles par défaut si aucun n'est configuré.
   *
   * @return list<array<string, mixed>> Modèles par défaut
   */
  public function defaultMessages(): array
  {
    return [[
      'id' => 'official-release',
      'label' => 'Sortie officielle',
      'email_subject' => '« {book_title} » est maintenant disponible',
      'body' => "Bonjour,\n\nLe livre « {book_title} » vient de paraître.\n\nDécouvrez-le dès maintenant : {book_link}\n\nKen Luamba Éditions",
    ]];
  }

  /**
   * Options de sélection Filament (id => libellé).
   *
   * @param Book|null $book Livre source
   * @return array<string, string> Options
   */
  public function optionsForBook(?Book $book): array
  {
    $options = [];

    foreach ($this->messagesForBook($book) as $message) {
      $id = $message['id'] ?? null;

      if (! is_string($id) || $id === '') {
        continue;
      }

      $options[$id] = $message['label'] ?? 'Message';
    }

    return $options;
  }

  /**
   * Retourne les modèles configurés pour un livre.
   *
   * @param Book|null $book Livre source
   * @return list<array<string, mixed>> Modèles
   */
  public function messagesForBook(?Book $book): array
  {
    if ($book === null) {
      return $this->defaultMessages();
    }

    return $this->normalizeStoredMessages($book->release_notification_messages);
  }

  /**
   * Résout l'objet email d'un modèle.
   *
   * @param Book $book Livre concerné
   * @param BookReleaseSubscription $subscription Inscription
   * @param string|null $messageId Identifiant du modèle
   * @return string Objet email
   */
  public function resolveEmailSubject(
    Book $book,
    BookReleaseSubscription $subscription,
    ?string $messageId = null,
  ): string {
    $message = $this->findMessage($book, $messageId);

    return $this->replacePlaceholders(
      (string) ($message['email_subject'] ?? 'Votre livre est disponible'),
      $book,
      $subscription,
    );
  }

  /**
   * Résout le corps d'un modèle.
   *
   * @param Book $book Livre concerné
   * @param BookReleaseSubscription $subscription Inscription
   * @param string|null $messageId Identifiant du modèle
   * @return string Corps du message
   */
  public function resolveBody(
    Book $book,
    BookReleaseSubscription $subscription,
    ?string $messageId = null,
  ): string {
    $message = $this->findMessage($book, $messageId);

    return $this->replacePlaceholders((string) ($message['body'] ?? ''), $book, $subscription);
  }

  /**
   * Remplace les variables dynamiques dans un texte.
   *
   * @param string $text Texte source
   * @param Book $book Livre concerné
   * @param BookReleaseSubscription $subscription Inscription
   * @return string Texte interpolé
   */
  public function replacePlaceholders(
    string $text,
    Book $book,
    BookReleaseSubscription $subscription,
  ): string {
    $frontendUrl = rtrim((string) config('app.frontend_url'), '/');
    $releaseDate = $book->release_date;

    $replacements = [
      '{subscriber_email}' => $subscription->email,
      '{book_title}' => $book->title,
      '{book_subtitle}' => $book->subtitle ?? '',
      '{release_date}' => $releaseDate?->locale('fr')->isoFormat('D MMMM YYYY') ?? '',
      '{release_date_short}' => $releaseDate?->format('d/m/Y') ?? '',
      '{book_link}' => $frontendUrl.'/livres/'.$book->slug,
    ];

    return str_replace(array_keys($replacements), array_values($replacements), $text);
  }

  /**
   * Trouve un modèle par identifiant.
   *
   * @param Book $book Livre source
   * @param string|null $messageId Identifiant du modèle
   * @return array<string, mixed> Modèle trouvé
   */
  private function findMessage(Book $book, ?string $messageId): array
  {
    $messages = $this->messagesForBook($book);

    if ($messageId !== null) {
      foreach ($messages as $message) {
        if (($message['id'] ?? null) === $messageId) {
          return $message;
        }
      }
    }

    return $messages[0] ?? $this->defaultMessages()[0];
  }

  /**
   * Génère un identifiant stable pour un modèle sans id persisté.
   *
   * @param array<string, mixed> $message Modèle source
   * @param string $body Corps du message
   * @param int $index Position dans la liste
   * @return string Identifiant stable
   */
  private static function resolveMessageId(array $message, string $body, int $index): string
  {
    $existingId = $message['id'] ?? null;

    if (is_string($existingId) && $existingId !== '') {
      return $existingId;
    }

    $label = trim((string) ($message['label'] ?? ''));

    if ($label !== '') {
      return 'message-'.Str::slug($label);
    }

    return 'message-'.$index.'-'.substr(md5($body), 0, 8);
  }
}
