<?php

namespace App\Services\BookRelease;

use App\Enums\BookReleaseDispatchStatus;
use App\Models\Book;
use App\Models\BookReleaseDispatchLog;
use App\Models\BookReleaseSubscription;
use App\Notifications\BookRelease\BookReleaseAvailableNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Envoie les alertes sortie aux inscrits et journalise les résultats.
 */
class BookReleaseDispatchService
{
  /**
   * Initialise le service avec le gestionnaire de messages.
   *
   * @param BookReleaseMessageService $messageService Service des modèles
   */
  public function __construct(
    private readonly BookReleaseMessageService $messageService,
  ) {}

  /**
   * Envoie un e-mail à une inscription.
   *
   * @param BookReleaseSubscription $subscription Inscription cible
   * @param string|null $messageId Identifiant du modèle
   * @return BookReleaseDispatchLog Journal d'envoi
   */
  public function sendToSubscription(
    BookReleaseSubscription $subscription,
    ?string $messageId = null,
  ): BookReleaseDispatchLog {
    $subscription->loadMissing('book');
    $book = $subscription->book;

    if ($book === null) {
      throw new \RuntimeException('Livre introuvable pour cette inscription.');
    }

    $subject = $this->messageService->resolveEmailSubject($book, $subscription, $messageId);
    $body = $this->messageService->resolveBody($book, $subscription, $messageId);

    try {
      Notification::route('mail', $subscription->email)->notify(
        new BookReleaseAvailableNotification($book, $subscription, $subject, $body),
      );

      $subscription->update(['notified_at' => now()]);

      return $this->logDispatch(
        $book,
        $subscription,
        $messageId,
        $subject,
        $body,
        BookReleaseDispatchStatus::Sent,
      );
    } catch (Throwable $exception) {
      report($exception);

      return $this->logDispatch(
        $book,
        $subscription,
        $messageId,
        $subject,
        $body,
        BookReleaseDispatchStatus::Failed,
        $exception->getMessage(),
      );
    }
  }

  /**
   * Envoie un e-mail à plusieurs inscriptions.
   *
   * @param Collection<int, BookReleaseSubscription> $subscriptions Inscriptions cibles
   * @param string|null $messageId Identifiant du modèle
   * @return array{sent:int, failed:int} Statistiques d'envoi
   */
  public function sendBulk(Collection $subscriptions, ?string $messageId = null): array
  {
    $sent = 0;
    $failed = 0;

    foreach ($subscriptions as $subscription) {
      $log = $this->sendToSubscription($subscription, $messageId);

      if ($log->status === BookReleaseDispatchStatus::Sent) {
        $sent++;
        continue;
      }

      $failed++;
    }

    return compact('sent', 'failed');
  }

  /**
   * Traite les envois automatiques programmés sur les livres.
   *
   * @return array{books:int, sent:int, failed:int} Statistiques globales
   */
  public function dispatchScheduled(): array
  {
    $books = Book::query()
      ->where('release_auto_notify_enabled', true)
      ->whereNotNull('release_auto_notify_at')
      ->where('release_auto_notify_at', '<=', now())
      ->whereNull('release_auto_notify_sent_at')
      ->get();

    $booksCount = 0;
    $sent = 0;
    $failed = 0;

    foreach ($books as $book) {
      $subscriptions = BookReleaseSubscription::query()
        ->where('book_id', $book->id)
        ->whereNull('notified_at')
        ->get();

      if ($subscriptions->isEmpty()) {
        $book->update(['release_auto_notify_sent_at' => now()]);
        continue;
      }

      $result = $this->sendBulk($subscriptions);
      $booksCount++;
      $sent += $result['sent'];
      $failed += $result['failed'];
      $book->update(['release_auto_notify_sent_at' => now()]);
    }

    return [
      'books' => $booksCount,
      'sent' => $sent,
      'failed' => $failed,
    ];
  }

  /**
   * Enregistre un envoi dans le journal.
   *
   * @param Book $book Livre concerné
   * @param BookReleaseSubscription $subscription Inscription
   * @param string|null $messageId Modèle utilisé
   * @param string $subject Objet email
   * @param string $body Corps email
   * @param BookReleaseDispatchStatus $status Statut
   * @param string|null $errorMessage Message d'erreur
   * @return BookReleaseDispatchLog Entrée créée
   */
  private function logDispatch(
    Book $book,
    BookReleaseSubscription $subscription,
    ?string $messageId,
    string $subject,
    string $body,
    BookReleaseDispatchStatus $status,
    ?string $errorMessage = null,
  ): BookReleaseDispatchLog {
    return BookReleaseDispatchLog::query()->create([
      'book_id' => $book->id,
      'book_release_subscription_id' => $subscription->id,
      'recipient_email' => $subscription->email,
      'message_id' => $messageId,
      'subject' => $subject,
      'body' => $body,
      'status' => $status,
      'scheduled_for' => $book->release_auto_notify_at,
      'sent_at' => $status === BookReleaseDispatchStatus::Sent ? now() : null,
      'error_message' => $errorMessage,
    ]);
  }
}
