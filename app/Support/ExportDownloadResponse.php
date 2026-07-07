<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Envoie un fichier d'export au navigateur puis le supprime du disque.
 */
class ExportDownloadResponse
{
  /**
   * Stream le téléchargement d'un fichier temporaire.
   *
   * @param string $path Chemin absolu du fichier généré
   * @return StreamedResponse Réponse HTTP de téléchargement
   */
  public static function stream(string $path): StreamedResponse
  {
    return response()->streamDownload(function () use ($path): void {
      readfile($path);
      @unlink($path);
    }, basename($path), [
      'Content-Type' => match (strtolower((string) pathinfo($path, PATHINFO_EXTENSION))) {
        'pdf' => 'application/pdf',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'epub' => 'application/epub+zip',
        default => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
      },
    ]);
  }
}
