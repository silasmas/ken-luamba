<?php

namespace App\Models;

use App\Enums\InvitationRsvpStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\Invitations\InvitationTokenGenerator;

/**
 * Modèle représentant une invitation individuelle à un événement.
 */
class Invitation extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'event_id',
    'full_name',
    'email',
    'phone',
    'organization',
    'token',
    'rsvp_status',
    'guest_message',
    'responded_at',
    'email_sent_at',
    'whatsapp_sent_at',
    'sms_sent_at',
    'admin_notes',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'rsvp_status' => InvitationRsvpStatus::class,
      'responded_at' => 'datetime',
      'email_sent_at' => 'datetime',
      'whatsapp_sent_at' => 'datetime',
      'sms_sent_at' => 'datetime',
    ];
  }

  /**
   * Génère un token unique à la création.
   */
  protected static function booted(): void
  {
    static::creating(function (Invitation $invitation): void {
      if ($invitation->token === null || $invitation->token === '') {
        $invitation->token = app(InvitationTokenGenerator::class)->generateUnique();
      }
    });
  }

  /**
   * Événement concerné par l'invitation.
   *
   * @return BelongsTo<Event, $this>
   */
  public function event(): BelongsTo
  {
    return $this->belongsTo(Event::class);
  }
}
