<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InvitationRsvpStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\InvitationResource;
use App\Models\Invitation;
use App\Services\Invitations\InvitationRsvpService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Contrôleur API public des invitations événements.
 */
class InvitationController extends Controller
{
  /**
   * Affiche une invitation via son token public.
   *
   * @param string $token Token d'invitation
   * @return InvitationResource|JsonResponse Invitation ou 404
   */
  public function show(string $token): InvitationResource|JsonResponse
  {
    $invitation = Invitation::query()
      ->with(['event.books'])
      ->where('token', $token)
      ->first();

    if ($invitation === null || $invitation->event === null || ! $invitation->event->is_published) {
      return response()->json([
        'message' => 'Invitation introuvable.',
      ], 404);
    }

    return new InvitationResource($invitation);
  }

  /**
   * Enregistre la réponse RSVP d'un invité.
   *
   * @param Request $request Requête avec statut et message
   * @param string $token Token d'invitation
   * @param InvitationRsvpService $rsvpService Service RSVP
   * @return InvitationResource|JsonResponse Réponse mise à jour
   */
  public function respond(
    Request $request,
    string $token,
    InvitationRsvpService $rsvpService,
  ): InvitationResource|JsonResponse {
    $validated = $request->validate([
      'status' => ['required', Rule::in([
        InvitationRsvpStatus::Attending->value,
        InvitationRsvpStatus::NotAttending->value,
      ])],
      'message' => ['nullable', 'string', 'max:2000'],
    ]);

    try {
      $invitation = $rsvpService->respond(
        $token,
        InvitationRsvpStatus::from($validated['status']),
        $validated['message'] ?? null,
      );
    } catch (ModelNotFoundException) {
      return response()->json([
        'message' => 'Invitation introuvable.',
      ], 404);
    }

    return new InvitationResource($invitation);
  }
}
