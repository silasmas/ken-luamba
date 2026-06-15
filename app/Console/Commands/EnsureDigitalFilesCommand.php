<?php

namespace App\Console\Commands;

use App\Enums\BookFormatType;
use App\Enums\DigitalFileType;
use App\Models\BookFormat;
use Database\Seeders\Support\SeederDigitalFileService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Génère les fichiers numériques manquants pour les formats actifs.
 */
class EnsureDigitalFilesCommand extends Command
{
  /**
   * Signature de la commande Artisan.
   *
   * @var string
   */
  protected $signature = 'digital:ensure-files';

  /**
   * Description affichée dans la liste des commandes.
   *
   * @var string
   */
  protected $description = 'Crée les fichiers EPUB/MP3 de démonstration manquants pour les formats numériques';

  /**
   * Génère les fichiers manquants sur le disque local.
   *
   * @return int Code de sortie
   */
  public function handle(): int
  {
    $generator = new SeederDigitalFileService();
    $created = 0;

    $formats = BookFormat::query()
      ->where('is_active', true)
      ->whereIn('type', [BookFormatType::Ebook->value, BookFormatType::Audiobook->value])
      ->with('book')
      ->get();

    foreach ($formats as $format) {
      $path = $format->digital_file_path;

      if ($path !== null && Storage::disk('local')->exists($path)) {
        continue;
      }

      $slug = $format->book?->slug ?? $format->sku;
      $title = $format->book?->title ?? 'Livre Ken Luamba';

      if ($format->type === BookFormatType::Ebook) {
        $path = $generator->generateDemoEpub('books/digital/'.$slug.'.epub', $title);
        $format->update([
          'digital_file_path' => $path,
          'digital_file_type' => DigitalFileType::Epub,
        ]);
      } else {
        $path = $generator->generateDemoMp3('books/digital/'.$slug.'.mp3');
        $format->update([
          'digital_file_path' => $path,
          'digital_file_type' => DigitalFileType::Mp3,
        ]);
      }

      $created++;
      $this->line('Fichier créé : '.$path);
    }

    $this->info($created.' fichier(s) numérique(s) généré(s).');

    return self::SUCCESS;
  }
}
