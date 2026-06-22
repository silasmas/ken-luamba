<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Télécharge le rapport Excel des invités non enregistrés après un import.
 */
class InvitationImportRejectDownloadController extends Controller
{
  /**
   * Envoie le fichier Excel généré lors du dernier import de l'utilisateur connecté.
   *
   * @param Request $request Requête HTTP avec session active
   * @return StreamedResponse Fichier Excel
   */
  public function __invoke(Request $request): StreamedResponse
  {
    $filename = $request->session()->get('invitation_import_reject_file');

    if (! is_string($filename) || $filename === '') {
      abort(404, 'Aucun rapport d\'import disponible.');
    }

    $path = storage_path('app/exports/'.$filename);

    if (! is_file($path)) {
      abort(404, 'Le rapport d\'import n\'est plus disponible.');
    }

    return response()->streamDownload(function () use ($path): void {
      readfile($path);
    }, $filename, [
      'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ]);
  }
}
