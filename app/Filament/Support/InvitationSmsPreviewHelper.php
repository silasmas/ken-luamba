<?php

namespace App\Filament\Support;

use App\Enums\InvitationDispatchChannel;
use App\Models\Event;
use App\Models\Invitation;
use App\Services\Invitations\InvitationMessageService;
use App\Services\Sms\SmsMessageAnalyzer;
use Illuminate\Support\HtmlString;

/**
 * Aperçu SMS et compteur de segments pour l'admin invitations.
 */
class InvitationSmsPreviewHelper
{
  /**
   * Génère l'aperçu HTML d'un modèle SMS avec données exemple ou invité réel.
   *
   * @param Event|null $event Événement source
   * @param string|null $messageId Identifiant du modèle
   * @param Invitation|null $invitation Invitation pour un rendu personnalisé
   * @return HtmlString Markup Filament
   */
  public static function previewHtml(
    ?Event $event,
    ?string $messageId = null,
    ?Invitation $invitation = null,
  ): HtmlString {
    $messageService = app(InvitationMessageService::class);
    $analyzer = app(SmsMessageAnalyzer::class);

    if ($invitation !== null) {
      $body = $messageService->resolveBody(
        $invitation,
        InvitationDispatchChannel::Sms,
        $messageId,
      );
    } else {
      $body = $messageService->previewTemplateBody($event, $messageId);
    }

    return $analyzer->formatPreviewHtml($body, $analyzer->analyze($body));
  }

  /**
   * Génère l'aperçu HTML à partir d'un corps brut (éditeur événement).
   *
   * @param string|null $body Contenu du modèle
   * @param Event|null $event Événement source
   * @return HtmlString Markup Filament
   */
  public static function previewRawBodyHtml(?string $body, ?Event $event): HtmlString
  {
    $messageService = app(InvitationMessageService::class);
    $analyzer = app(SmsMessageAnalyzer::class);
    $rendered = $messageService->previewRawTemplateBody($body ?? '', $event);

    return $analyzer->formatPreviewHtml($rendered, $analyzer->analyze($rendered));
  }
}
