<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Journal d'un email réellement envoyé par l'application (événement MessageSent).
 */
class MailSendLog extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'to',
    'subject',
    'mailer',
  ];
}
