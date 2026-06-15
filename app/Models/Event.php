<?php

namespace App\Models;

use App\Enums\EventType;
use App\Services\Invitations\InvitationMessageService;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Modèle représentant un événement Ken Luamba (lancement, cérémonie…).
 */
class Event extends Model
{
  use HasUuids;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'title',
    'slug',
    'type',
    'description',
    'welcome_message',
    'invitation_messages',
    'invitation_auto_send_enabled',
    'invitation_auto_send_at',
    'invitation_auto_send_sent_at',
    'invitation_auto_send_channel',
    'invitation_auto_send_message_id',
    'starts_at',
    'ends_at',
    'location',
    'venue_details',
    'is_published',
  ];

  /**
   * Casts des attributs du modèle.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'type' => EventType::class,
      'starts_at' => 'datetime',
      'ends_at' => 'datetime',
      'is_published' => 'boolean',
      'invitation_messages' => 'array',
      'invitation_auto_send_enabled' => 'boolean',
      'invitation_auto_send_at' => 'datetime',
      'invitation_auto_send_sent_at' => 'datetime',
    ];
  }

  /**
   * Génère un slug unique à la création si absent.
   */
  protected static function booted(): void
  {
    static::creating(function (Event $event): void {
      if ($event->slug === null || $event->slug === '') {
        $baseSlug = Str::slug($event->title);
        $slug = $baseSlug;
        $suffix = 1;

        while (static::query()->where('slug', $slug)->exists()) {
          $slug = $baseSlug.'-'.$suffix;
          $suffix++;
        }

        $event->slug = $slug;
      }
    });

    static::saving(function (Event $event): void {
      $event->invitation_messages = app(InvitationMessageService::class)
        ->normalizeStoredMessages($event->invitation_messages);

      if ($event->isDirty([
        'invitation_auto_send_enabled',
        'invitation_auto_send_at',
        'invitation_auto_send_channel',
        'invitation_auto_send_message_id',
      ])) {
        $event->invitation_auto_send_sent_at = null;
      }
    });
  }

  /**
   * Livres associés à l'événement.
   *
   * @return BelongsToMany<Book, $this>
   */
  public function books(): BelongsToMany
  {
    return $this->belongsToMany(Book::class);
  }

  /**
   * Invitations liées à l'événement.
   *
   * @return HasMany<Invitation, $this>
   */
  public function invitations(): HasMany
  {
    return $this->hasMany(Invitation::class);
  }
}
