<?php

namespace App\Notifications\BookRelease;

use App\Models\Book;
use App\Models\BookReleaseSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail informant qu'un livre attendu est disponible.
 */
class BookReleaseAvailableNotification extends Notification
{
  use Queueable;

  /**
   * Initialise la notification de sortie.
   *
   * @param Book $book Livre concerné
   * @param BookReleaseSubscription $subscription Inscription
   * @param string $subject Objet de l'e-mail
   * @param string $body Corps du message
   */
  public function __construct(
    private readonly Book $book,
    private readonly BookReleaseSubscription $subscription,
    private readonly string $subject,
    private readonly string $body,
  ) {}

  /**
   * Canaux de diffusion.
   *
   * @param mixed $notifiable Destinataire
   * @return list<string>
   */
  public function via(mixed $notifiable): array
  {
    return ['mail'];
  }

  /**
   * Construit l'e-mail de sortie.
   *
   * @param mixed $notifiable Destinataire
   * @return MailMessage Message email
   */
  public function toMail(mixed $notifiable): MailMessage
  {
    $bookUrl = rtrim((string) config('app.frontend_url'), '/').'/livres/'.$this->book->slug;
    $lines = array_values(array_filter(
      explode("\n", $this->body),
      fn (string $line): bool => trim($line) !== '',
    ));

    $mail = (new MailMessage)
      ->subject($this->subject)
      ->greeting('Bonjour,');

    foreach ($lines as $line) {
      if (str_contains($line, $bookUrl)) {
        continue;
      }

      $mail->line($line);
    }

    return $mail
      ->action('Découvrir le livre', $bookUrl)
      ->salutation('Ken Luamba Éditions');
  }
}
