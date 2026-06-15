<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ContactSetting;
use App\Support\ContactLinkHelper;
use Illuminate\Http\JsonResponse;

/**
 * Expose les coordonnées de contact configurées dans l'admin.
 */
class ContactController extends Controller
{
  /**
   * Retourne les informations de contact pour la page publique.
   *
   * @return JsonResponse Coordonnées et texte d'introduction
   */
  public function __invoke(): JsonResponse
  {
    $settings = ContactSetting::instance();

    return response()->json([
      'data' => [
        'introDescription' => $settings->intro_description,
        'physicalAddress' => [
          'label' => 'Adresse',
          'value' => $settings->physical_address,
        ],
        'phonePrimary' => [
          'label' => 'Téléphone',
          'value' => $settings->phone_primary,
          'href' => ContactLinkHelper::telHref($settings->phone_primary),
          'action' => 'Appeler ↗',
        ],
        'phoneSecondary' => [
          'label' => 'WhatsApp',
          'value' => $settings->phone_secondary,
          'href' => ContactLinkHelper::whatsappHref($settings->phone_secondary),
          'action' => 'Écrire ↗',
        ],
        'email' => [
          'label' => 'Email',
          'value' => $settings->email,
          'href' => ContactLinkHelper::mailtoHref($settings->email),
          'action' => 'Envoyer ↗',
        ],
      ],
    ]);
  }
}
