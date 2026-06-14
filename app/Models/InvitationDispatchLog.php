<?php

namespace App\Models;

use App\Enums\InvitationDispatchChannel;
use App\Enums\InvitationDispatchStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Historique d'un envoi de message d'invitation (email, SMS, WhatsApp).
 */
class InvitationDispatchLog extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'invitation_id',
    'event_id',
    'sent_by',
    'channel',
    'recipient',
    'recipient_name',
    'message_template_id',
    'message_body',
    'status',
    'provider_response',
    'sent_at',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'channel' => InvitationDispatchChannel::class,
      'status' => InvitationDispatchStatus::class,
      'sent_at' => 'datetime',
    ];
  }

  /**
   * Invitation concernée par l'envoi.
   *
   * @return BelongsTo<Invitation, $this>
   */
  public function invitation(): BelongsTo
  {
    return $this->belongsTo(Invitation::class);
  }

  /**
   * Événement lié à l'envoi.
   *
   * @return BelongsTo<Event, $this>
   */
  public function event(): BelongsTo
  {
    return $this->belongsTo(Event::class);
  }

  /**
   * Utilisateur admin ayant déclenché l'envoi.
   *
   * @return BelongsTo<User, $this>
   */
  public function sender(): BelongsTo
  {
    return $this->belongsTo(User::class, 'sent_by');
  }
}
