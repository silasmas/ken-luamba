<?php

namespace App\Services\Invitations;

use App\Enums\InvitationDispatchChannel;
use App\Models\Event;
use App\Models\Invitation;
use Filament\Forms\Components\RichEditor\RichContentRenderer;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Gère les modèles de messages d'invitation et le remplacement des variables dynamiques.
 */
class InvitationMessageService
{
  /**
   * Liste des variables disponibles dans les modèles de messages.
   */
  public const PLACEHOLDER_HINT = '{guest_name}, {guest_email}, {guest_phone}, {guest_organization}, {event_title}, {event_type}, {event_date}, {event_date_short}, {event_time}, {event_location}, {event_venue_details}, {event_description}, {event_welcome_message}, {event_books}, {invitation_link}, {rsvp_status}';

  /**
   * Retourne la définition de chaque variable (token => description).
   *
   * @return array<string, string> Variables et leur utilisation
   */
  public static function placeholderDefinitions(): array
  {
    return [
      '{guest_name}' => 'Nom complet de l\'invité.',
      '{guest_email}' => 'Adresse email de l\'invité.',
      '{guest_phone}' => 'Numéro de téléphone ou WhatsApp de l\'invité.',
      '{guest_organization}' => 'Type d\'invité (texte libre : VIP, VVIP, Presse…).',
      '{event_title}' => 'Titre de l\'événement.',
      '{event_type}' => 'Type d\'événement (lancement, cérémonie, etc.).',
      '{event_date}' => 'Date et heure complètes de début (format long).',
      '{event_date_short}' => 'Date de début au format court.',
      '{event_time}' => 'Heure de début uniquement.',
      '{event_location}' => 'Lieu principal de l\'événement.',
      '{event_venue_details}' => 'Détails complémentaires du lieu.',
      '{event_description}' => 'Description de l\'événement.',
      '{event_welcome_message}' => 'Message d\'accueil affiché sur la page RSVP.',
      '{event_books}' => 'Titres des livres liés à l\'événement, séparés par des virgules.',
      '{invitation_link}' => 'Lien personnel de réponse à l\'invitation.',
      '{rsvp_status}' => 'Statut RSVP actuel de l\'invité (en attente, présent, absent).',
    ];
  }

  /**
   * Retourne les modèles configurés pour un canal donné.
   *
   * @param Event|null $event Événement source
   * @param InvitationDispatchChannel $channel Canal cible
   * @return list<array<string, mixed>> Modèles filtrés
   */
  public function templatesForChannel(?Event $event, InvitationDispatchChannel $channel): array
  {
    if ($event === null) {
      return [];
    }

    $messages = is_array($event->invitation_messages) ? $event->invitation_messages : [];

    return array_values(array_filter(
      $messages,
      fn (array $message): bool => $this->messageSupportsChannel($message, $channel),
    ));
  }

  /**
   * Indique si un modèle de message active un canal donné.
   *
   * @param array<string, mixed> $message Modèle enregistré
   * @param InvitationDispatchChannel $channel Canal cible
   * @return bool True si le canal est coché sur le modèle
   */
  private function messageSupportsChannel(array $message, InvitationDispatchChannel $channel): bool
  {
    $channels = $message['channels'] ?? [];

    if (! is_array($channels)) {
      return false;
    }

    $normalized = array_map(
      fn (mixed $value): string => is_string($value) ? strtolower(trim($value)) : '',
      $channels,
    );

    return in_array($channel->value, $normalized, true);
  }

  /**
   * Retourne les options de sélection pour un canal (id => libellé).
   *
   * @param Event|null $event Événement source
   * @param InvitationDispatchChannel $channel Canal cible
   * @return array<string, string> Options pour un Select Filament
   */
  public function optionsForChannel(?Event $event, InvitationDispatchChannel $channel): array
  {
    $options = [];

    foreach ($this->templatesForChannel($event, $channel) as $message) {
      $id = $message['id'] ?? null;

      if (! is_string($id) || $id === '') {
        continue;
      }

      $options[$id] = $message['label'] ?? 'Message';
    }

    return $options;
  }

  /**
   * Indique si l'événement possède au moins un modèle pour le canal.
   *
   * @param Event|null $event Événement source
   * @param InvitationDispatchChannel $channel Canal cible
   * @return bool True si au moins un modèle existe
   */
  public function hasTemplatesForChannel(?Event $event, InvitationDispatchChannel $channel): bool
  {
    return $this->templatesForChannel($event, $channel) !== [];
  }

  /**
   * Indique si un canal est activé pour l'événement (au moins un modèle l'utilise).
   *
   * @param Event|null $event Événement source
   * @param InvitationDispatchChannel $channel Canal cible
   * @return bool True si le canal peut être proposé dans l'admin
   */
  public function isChannelEnabled(?Event $event, InvitationDispatchChannel $channel): bool
  {
    if ($event === null) {
      return false;
    }

    $messages = is_array($event->invitation_messages) ? $event->invitation_messages : [];

    if ($messages === []) {
      return true;
    }

    return $this->hasTemplatesForChannel($event, $channel);
  }

  /**
   * Retourne les canaux activés pour un événement.
   *
   * @param Event|null $event Événement source
   * @return list<InvitationDispatchChannel> Canaux disponibles
   */
  public function enabledChannels(?Event $event): array
  {
    if ($event === null) {
      return [];
    }

    return array_values(array_filter(
      InvitationDispatchChannel::cases(),
      fn (InvitationDispatchChannel $channel): bool => $this->isChannelEnabled($event, $channel),
    ));
  }

  /**
   * Retourne les options Select Filament pour les canaux activés.
   *
   * @param Event|null $event Événement source
   * @return array<string, string> Valeur => libellé
   */
  public function enabledChannelOptions(?Event $event): array
  {
    $options = [];

    foreach ($this->enabledChannels($event) as $channel) {
      $options[$channel->value] = $channel->label();
    }

    return $options;
  }

  /**
   * Retourne un modèle par identifiant.
   *
   * @param Event $event Événement source
   * @param string $messageId Identifiant du modèle
   * @return array<string, mixed>|null Modèle trouvé ou null
   */
  public function findTemplate(Event $event, string $messageId): ?array
  {
    $messages = is_array($event->invitation_messages) ? $event->invitation_messages : [];

    foreach ($messages as $message) {
      if (($message['id'] ?? null) === $messageId) {
        return $message;
      }
    }

    return null;
  }

  /**
   * Compose le corps du message pour un canal et un modèle éventuel.
   *
   * @param Invitation $invitation Invitation cible
   * @param InvitationDispatchChannel $channel Canal d'envoi
   * @param string|null $messageId Identifiant du modèle choisi
   * @return string Message final
   */
  public function resolveBody(
    Invitation $invitation,
    InvitationDispatchChannel $channel,
    ?string $messageId = null,
  ): string {
    $invitation->loadMissing(['event.books']);
    $event = $invitation->event;

    if ($messageId !== null && $event !== null) {
      $template = $this->findTemplate($event, $messageId);

      if (
        $template !== null
        && $this->messageSupportsChannel($template, $channel)
        && filled($template['body'] ?? null)
      ) {
        $rawBody = $this->normalizeTemplateContent($template['body'] ?? '');

        if ($channel === InvitationDispatchChannel::Email && $this->isRichContent($rawBody)) {
          return $this->renderHtml($rawBody, $invitation);
        }

        $plain = $this->renderPlainText($rawBody, $invitation);

        return $channel === InvitationDispatchChannel::Sms
          ? $this->formatForSms($plain)
          : $plain;
      }
    }

    $default = $this->buildDefaultMessageBody($invitation);

    return $channel === InvitationDispatchChannel::Sms
      ? $this->formatForSms($default)
      : $default;
  }

  /**
   * Prévisualise un modèle SMS enregistré sur un événement.
   *
   * @param Event|null $event Événement source
   * @param string|null $messageId Identifiant du modèle
   * @return string Message SMS rendu avec données exemple
   */
  public function previewTemplateBody(?Event $event, ?string $messageId = null): string
  {
    if ($event === null) {
      return '';
    }

    if ($messageId !== null) {
      $template = $this->findTemplate($event, $messageId);

      if (
        $template !== null
        && $this->messageSupportsChannel($template, InvitationDispatchChannel::Sms)
        && filled($template['body'] ?? null)
      ) {
        return $this->previewRawTemplateBody($template['body'] ?? '', $event);
      }
    }

    $templates = $this->templatesForChannel($event, InvitationDispatchChannel::Sms);
    $first = $templates[0] ?? null;

    if ($first !== null && filled($first['body'] ?? null)) {
      return $this->previewRawTemplateBody($first['body'] ?? '', $event);
    }

    return $this->formatForSms($this->applyPlaceholderTokens(
      'Bonjour {guest_name}, vous êtes invité(e) à {event_title}.',
      $this->samplePlaceholderTokens($event),
    ));
  }

  /**
   * Prévisualise un corps brut de modèle SMS avec des données exemple.
   *
   * @param mixed $template Contenu du modèle (texte, HTML ou JSON TipTap)
   * @param Event|null $event Événement source
   * @return string Message SMS rendu
   */
  public function previewRawTemplateBody(mixed $template, ?Event $event): string
  {
    $tokens = $this->samplePlaceholderTokens($event);
    $plain = $this->renderPlainFromTemplate($template, $tokens, $this->sampleMergeTagValues($event));

    return $this->formatForSms($plain);
  }

  /**
   * Compose l'objet d'un email d'invitation.
   *
   * @param Invitation $invitation Invitation cible
   * @param string|null $messageId Identifiant du modèle choisi
   * @return string Objet email final
   */
  public function resolveEmailSubject(Invitation $invitation, ?string $messageId = null): string
  {
    $invitation->loadMissing('event');
    $event = $invitation->event;

    if ($messageId !== null && $event !== null) {
      $template = $this->findTemplate($event, $messageId);

      if (
        $template !== null
        && $this->messageSupportsChannel($template, InvitationDispatchChannel::Email)
        && filled($template['email_subject'] ?? null)
      ) {
        return $this->render((string) $template['email_subject'], $invitation);
      }
    }

    return $this->render('Invitation — {event_title}', $invitation);
  }

  /**
   * Remplace les variables dynamiques dans un modèle de texte (compatibilité).
   *
   * @param string $template Texte contenant des variables
   * @param Invitation $invitation Invitation source des données invité
   * @return string Texte rendu en clair
   */
  public function render(string $template, Invitation $invitation): string
  {
    return $this->renderPlainText($template, $invitation);
  }

  /**
   * Indique si le contenu provient de l'éditeur riche Filament.
   *
   * @param string $content Contenu brut
   * @return bool True si HTML ou document TipTap JSON
   */
  public function isRichContent(string $content): bool
  {
    $trimmed = trim($content);

    if ($trimmed === '') {
      return false;
    }

    if (str_starts_with($trimmed, '{') && str_contains($trimmed, '"type"')) {
      return true;
    }

    return str_contains($content, '<');
  }

  /**
   * Rend un modèle en texte brut (SMS, WhatsApp, sujets email).
   *
   * @param string $template Contenu du modèle
   * @param Invitation $invitation Invitation source
   * @return string Texte sans balises HTML
   */
  public function renderPlainText(string $template, Invitation $invitation): string
  {
    return $this->renderPlainFromTemplate(
      $template,
      $this->placeholders($invitation),
      $this->mergeTagValues($invitation),
    );
  }

  /**
   * Rend un modèle en HTML sécurisé (emails et page publique).
   *
   * @param string $template Contenu du modèle
   * @param Invitation $invitation Invitation source
   * @return string HTML sanitisé
   */
  public function renderHtml(string $template, Invitation $invitation): string
  {
    $template = $this->normalizeTemplateContent($template);
    $template = $this->normalizeTemplateTokens($template);
    $tokens = $this->placeholders($invitation);

    if ($this->isRichContent($template)) {
      $workingTemplate = $this->isTipTapDocument($template)
        ? $template
        : $this->applyPlaceholderTokens($template, $tokens);

      return RichContentRenderer::make($workingTemplate)
        ->mergeTags($this->mergeTagValues($invitation))
        ->toHtml();
    }

    return nl2br(e($this->applyPlaceholderTokens($template, $tokens)));
  }

  /**
   * Retourne les valeurs de merge tags pour l'éditeur riche Filament.
   *
   * @param Invitation $invitation Invitation source
   * @return array<string, string> Identifiants sans accolades => valeur
   */
  public function mergeTagValues(Invitation $invitation): array
  {
    $values = [];

    foreach ($this->placeholders($invitation) as $token => $value) {
      $values[trim($token, '{}')] = $value;
    }

    return $values;
  }

  /**
   * Construit le tableau des variables disponibles pour une invitation.
   *
   * @param Invitation $invitation Invitation source
   * @return array<string, string> Paires variable => valeur
   */
  public function placeholders(Invitation $invitation): array
  {
    $invitation->loadMissing(['event.books']);
    $event = $invitation->event;
    $startsAt = $event?->starts_at instanceof Carbon
      ? $event->starts_at->timezone(config('app.timezone'))->locale('fr')
      : null;

    return [
      '{guest_name}' => $invitation->full_name,
      '{guest_email}' => (string) ($invitation->email ?? ''),
      '{guest_phone}' => (string) ($invitation->phone ?? ''),
      '{guest_organization}' => (string) ($invitation->organization ?? ''),
      '{event_title}' => (string) ($event?->title ?? ''),
      '{event_type}' => (string) ($event?->type?->label() ?? ''),
      '{event_date}' => $startsAt?->isoFormat('dddd D MMMM YYYY [à] HH[h]mm') ?? '',
      '{event_date_short}' => $startsAt?->isoFormat('D MMMM YYYY') ?? '',
      '{event_time}' => $startsAt?->isoFormat('HH[h]mm') ?? '',
      '{event_location}' => (string) ($event?->location ?? ''),
      '{event_venue_details}' => (string) ($event?->venue_details ?? ''),
      '{event_description}' => (string) ($event?->description ?? ''),
      '{event_welcome_message}' => trim(strip_tags((string) ($event?->welcome_message ?? ''))),
      '{event_books}' => $event?->books?->pluck('title')->filter()->implode(', ') ?? '',
      '{invitation_link}' => app(InvitationLinkService::class)->publicUrl($invitation),
      '{rsvp_status}' => (string) ($invitation->rsvp_status?->label() ?? ''),
    ];
  }

  /**
   * Assure qu'un modèle possède un identifiant unique avant enregistrement.
   *
   * @param array<int, array<string, mixed>>|null $messages Modèles saisis dans le formulaire
   * @return array<int, array<string, mixed>> Modèles normalisés
   */
  public function normalizeStoredMessages(?array $messages): array
  {
    if ($messages === null) {
      return [];
    }

    return array_values(array_map(function (array $message): array {
      if (! filled($message['id'] ?? null)) {
        $message['id'] = (string) Str::uuid();
      }

      $message['channels'] = array_values(array_unique(array_filter(
        $message['channels'] ?? [],
        fn (mixed $channel): bool => is_string($channel) && $channel !== '',
      )));

      return $message;
    }, $messages));
  }

  /**
   * Compose le message texte par défaut lorsqu'aucun modèle n'est sélectionné.
   *
   * @param Invitation $invitation Invitation cible
   * @return string Message par défaut
   */
  private function buildDefaultMessageBody(Invitation $invitation): string
  {
    return app(InvitationLinkService::class)->defaultMessageBody($invitation);
  }

  /**
   * Rend un modèle en texte brut en remplaçant toutes les variables dynamiques.
   *
   * @param mixed $template Contenu du modèle
   * @param array<string, string> $tokens Variables => valeurs
   * @param array<string, string> $mergeTags Merge tags Filament (sans accolades)
   * @return string Texte final sans balises
   */
  private function renderPlainFromTemplate(
    mixed $template,
    array $tokens,
    array $mergeTags,
  ): string {
    $template = $this->normalizeTemplateContent($template);
    $template = $this->normalizeTemplateTokens($template);

    if ($this->isRichContent($template)) {
      $workingTemplate = $this->isTipTapDocument($template)
        ? $template
        : $this->applyPlaceholderTokens($template, $tokens);

      $text = RichContentRenderer::make($workingTemplate)
        ->mergeTags($mergeTags)
        ->toText();
      $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

      return $this->applyPlaceholderTokens($text, $tokens);
    }

    return $this->applyPlaceholderTokens($template, $tokens);
  }

  /**
   * Indique si le contenu est un document TipTap JSON (Filament RichEditor).
   *
   * @param string $content Contenu brut
   * @return bool True si JSON TipTap
   */
  private function isTipTapDocument(string $content): bool
  {
    $trimmed = trim($content);

    return str_starts_with($trimmed, '{') && str_contains($trimmed, '"type"');
  }

  /**
   * Normalise le contenu brut d'un modèle (string HTML/JSON ou document TipTap).
   *
   * @param mixed $content Contenu enregistré dans le formulaire
   * @return string Contenu exploitable par le moteur de rendu
   */
  public function normalizeTemplateContent(mixed $content): string
  {
    if (is_array($content)) {
      $encoded = json_encode($content, JSON_UNESCAPED_UNICODE);

      return is_string($encoded) ? $encoded : '';
    }

    if (! is_string($content)) {
      return '';
    }

    return $content;
  }

  /**
   * Remplace les variables dynamiques dans un modèle déjà normalisé.
   *
   * @param string $template Contenu du modèle
   * @param array<string, string> $tokens Variables => valeurs
   * @return string Texte avec variables remplacées
   */
  private function applyPlaceholderTokens(string $template, array $tokens): string
  {
    $template = $this->normalizeTemplateTokens($template);

    return str_replace(array_keys($tokens), array_values($tokens), $template);
  }

  /**
   * Normalise les variables mal saisies (ex. {{ {guest_name} }}).
   *
   * @param string $template Contenu brut
   * @return string Modèle normalisé
   */
  private function normalizeTemplateTokens(string $template): string
  {
    $normalized = preg_replace('/\{\{\s*\{([^}]+)\}\s*\}\}/', '{$1}', $template) ?? $template;

    return preg_replace('/\{\{\s*([^}\s]+)\s*\}\}/', '{$1}', $normalized) ?? $normalized;
  }

  /**
   * Indique si des variables dynamiques n'ont pas été remplacées.
   *
   * @param string $text Message rendu
   * @return bool True si des tokens {variable} subsistent
   */
  public function containsUnresolvedPlaceholders(string $text): bool
  {
    return preg_match('/\{[a-z_]+\}/', $text) === 1;
  }

  /**
   * Adapte un texte SMS (GSM-7, sans markdown, texte compact).
   *
   * @param string $text Message rendu
   * @return string Texte SMS optimisé
   */
  private function formatForSms(string $text): string
  {
    $text = strip_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\*([^*\n]+)\*/', '$1', $text) ?? $text;
    $text = preg_replace('/\*+/', '', $text) ?? $text;
    $text = Str::transliterate($text);
    $text = preg_replace("/\s*\n\s*/", ' ', $text) ?? $text;
    $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;

    return trim($text);
  }

  /**
   * Remplace les variables d'un modèle avec des valeurs exemple pour l'aperçu admin.
   *
   * @param string $template Contenu du modèle
   * @param Event|null $event Événement source
   * @return string Texte rendu
   */
  private function renderSampleText(string $template, ?Event $event): string
  {
    return $this->applyPlaceholderTokens($template, $this->samplePlaceholderTokens($event));
  }

  /**
   * Retourne des valeurs exemple pour l'aperçu admin.
   *
   * @param Event|null $event Événement source
   * @return array<string, string> Variables => valeurs
   */
  private function samplePlaceholderTokens(?Event $event): array
  {
    $startsAt = $event?->starts_at instanceof Carbon
      ? $event->starts_at->timezone(config('app.timezone'))->locale('fr')
      : null;

    return [
      '{guest_name}' => 'Jean Dupont',
      '{guest_email}' => 'invite@exemple.com',
      '{guest_phone}' => '+243 812 345 678',
      '{guest_organization}' => 'VIP',
      '{event_title}' => (string) ($event?->title ?? 'Cérémonie de lancement'),
      '{event_type}' => (string) ($event?->type?->label() ?? 'Lancement'),
      '{event_date}' => $startsAt?->isoFormat('dddd D MMMM YYYY [à] HH[h]mm') ?? 'samedi 27 juin 2026 à 18h00',
      '{event_date_short}' => $startsAt?->isoFormat('D MMMM YYYY') ?? '27 juin 2026',
      '{event_time}' => $startsAt?->isoFormat('HH[h]mm') ?? '18h00',
      '{event_location}' => (string) ($event?->location ?? 'Kinshasa'),
      '{event_venue_details}' => (string) ($event?->venue_details ?? 'Salle principale'),
      '{event_description}' => (string) ($event?->description ?? 'Description de l\'événement'),
      '{event_welcome_message}' => trim(strip_tags((string) ($event?->welcome_message ?? ''))),
      '{event_books}' => $event?->books?->pluck('title')->filter()->implode(', ') ?: 'Livre 1, Livre 2',
      '{invitation_link}' => rtrim((string) env('FRONTEND_URL', config('app.url')), '/').'/'.trim((string) config('invitations.public_path', 'i'), '/').'/abc12XY9z0',
      '{rsvp_status}' => 'En attente',
    ];
  }

  /**
   * Retourne les merge tags exemple pour l'éditeur riche (aperçu admin).
   *
   * @param Event|null $event Événement source
   * @return array<string, string> Identifiants sans accolades => valeur
   */
  private function sampleMergeTagValues(?Event $event): array
  {
    $values = [];

    foreach ($this->samplePlaceholderTokens($event) as $token => $value) {
      $values[trim($token, '{}')] = $value;
    }

    return $values;
  }
}
