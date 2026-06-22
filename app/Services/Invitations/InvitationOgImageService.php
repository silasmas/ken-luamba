<?php

namespace App\Services\Invitations;

use App\Models\Book;
use App\Models\Event;
use App\Services\Books\BookCoverService;
use GdImage;
use Illuminate\Support\Facades\Storage;

/**
 * Génère une image PNG d'aperçu social (Open Graph / WhatsApp) pour une invitation.
 */
class InvitationOgImageService
{
  private const CANVAS_WIDTH = 1200;

  private const CANVAS_HEIGHT = 630;

  /**
   * Initialise le service avec le résolveur de couvertures.
   *
   * @param BookCoverService $bookCoverService Service de couvertures livres
   */
  public function __construct(
    private readonly BookCoverService $bookCoverService,
  ) {}

  /**
   * Construit l'URL publique de l'image d'aperçu pour un token d'invitation.
   *
   * @param string $token Token public d'invitation
   * @return string URL absolue HTTPS
   */
  public function publicUrlForToken(string $token): string
  {
    return url('/api/v1/invitations/'.$token.'/share-image.png');
  }

  /**
   * Génère le contenu binaire PNG pour l'aperçu social d'un événement.
   *
   * @param Event $event Événement source (livres chargés)
   * @return string Contenu PNG
   */
  public function generatePng(Event $event): string
  {
    $coverPaths = $this->resolveCoverPaths($event);

    if (count($coverPaths) === 0) {
      return $this->encodeCanvas($this->createFallbackCanvas($event->title));
    }

    if (count($coverPaths) === 1) {
      return $this->encodeCanvas($this->createSingleCoverCanvas($coverPaths[0], $event->title));
    }

    return $this->encodeCanvas($this->createMultiCoverCanvas($coverPaths, $event->title));
  }

  /**
   * Résout les chemins locaux des couvertures associées à l'événement.
   *
   * @param Event $event Événement avec relation books
   * @return list<string> Chemins absolus des fichiers image
   */
  private function resolveCoverPaths(Event $event): array
  {
    $event->loadMissing('books');
    $paths = [];

    foreach ($event->books->unique('id') as $book) {
      $localPath = $this->resolveLocalCoverPath($book);

      if ($localPath !== null) {
        $paths[] = $localPath;
      }
    }

    return array_slice($paths, 0, 6);
  }

  /**
   * Retourne le chemin local d'une couverture ou null.
   *
   * @param Book $book Livre cible
   * @return string|null Chemin absolu
   */
  private function resolveLocalCoverPath(Book $book): ?string
  {
    if (filled($book->cover_image) && Storage::disk('public')->exists($book->cover_image)) {
      return Storage::disk('public')->path($book->cover_image);
    }

    $this->bookCoverService->url($book);
    $book->refresh();

    if (filled($book->cover_image) && Storage::disk('public')->exists($book->cover_image)) {
      return Storage::disk('public')->path($book->cover_image);
    }

    return null;
  }

  /**
   * Charge une ressource GD depuis un fichier image.
   *
   * @param string $path Chemin absolu du fichier
   * @return GdImage|null Image GD ou null si échec
   */
  private function loadImage(string $path): ?GdImage
  {
    $mime = mime_content_type($path) ?: '';
    $image = match (true) {
      str_contains($mime, 'png') => @imagecreatefrompng($path),
      str_contains($mime, 'webp') => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
      default => @imagecreatefromjpeg($path),
    };

    return $image instanceof GdImage ? $image : null;
  }

  /**
   * Crée un canevas aux couleurs de la charte.
   *
   * @return GdImage Canevas vide
   */
  private function createBaseCanvas(): GdImage
  {
    $canvas = imagecreatetruecolor(self::CANVAS_WIDTH, self::CANVAS_HEIGHT);
    $background = imagecolorallocate($canvas, 245, 240, 232);
    imagefilledrectangle($canvas, 0, 0, self::CANVAS_WIDTH, self::CANVAS_HEIGHT, $background);

    return $canvas;
  }

  /**
   * Dessine un titre en bas du canevas.
   *
   * @param GdImage $canvas Canevas cible
   * @param string $title Titre de l'événement
   * @return void
   */
  private function drawTitle(GdImage $canvas, string $title): void
  {
    $textColor = imagecolorallocate($canvas, 17, 17, 17);
    $fontPath = $this->resolveFontPath();
    $truncated = $this->truncateText($title, 70);

    if ($fontPath === null) {
      imagestring($canvas, 5, 40, self::CANVAS_HEIGHT - 60, $truncated, $textColor);

      return;
    }

    imagettftext($canvas, 28, 0, 40, self::CANVAS_HEIGHT - 40, $textColor, $fontPath, $truncated);
  }

  /**
   * Crée l'aperçu avec une seule couverture centrée.
   *
   * @param string $coverPath Chemin de la couverture
   * @param string $title Titre de l'événement
   * @return GdImage Canevas final
   */
  private function createSingleCoverCanvas(string $coverPath, string $title): GdImage
  {
    $canvas = $this->createBaseCanvas();
    $cover = $this->loadImage($coverPath);

    if ($cover === null) {
      return $this->createFallbackCanvas($title);
    }

    $this->pasteResizedCover($canvas, $cover, self::CANVAS_WIDTH / 2, 260, 360, 480);
    imagedestroy($cover);
    $this->drawTitle($canvas, $title);

    return $canvas;
  }

  /**
   * Crée l'aperçu composite avec plusieurs couvertures.
   *
   * @param list<string> $coverPaths Chemins des couvertures
   * @param string $title Titre de l'événement
   * @return GdImage Canevas final
   */
  private function createMultiCoverCanvas(array $coverPaths, string $title): GdImage
  {
    $canvas = $this->createBaseCanvas();
    $layout = $this->computeLayout(count($coverPaths));
    $gridWidth = ($layout['width'] * $layout['columns']) + ($layout['gap'] * ($layout['columns'] - 1));
    $rows = (int) ceil(count($coverPaths) / $layout['columns']);
    $gridHeight = ($layout['height'] * $rows) + ($layout['gap'] * max(0, $rows - 1));
    $startX = (self::CANVAS_WIDTH - $gridWidth) / 2;
    $startY = (self::CANVAS_HEIGHT - $gridHeight - 80) / 2;

    foreach ($coverPaths as $index => $coverPath) {
      $cover = $this->loadImage($coverPath);

      if ($cover === null) {
        continue;
      }

      $column = $index % $layout['columns'];
      $row = intdiv($index, $layout['columns']);
      $centerX = $startX + ($column * ($layout['width'] + $layout['gap'])) + ($layout['width'] / 2);
      $centerY = $startY + ($row * ($layout['height'] + $layout['gap'])) + ($layout['height'] / 2);
      $this->pasteResizedCover($canvas, $cover, $centerX, $centerY, $layout['width'], $layout['height']);
      imagedestroy($cover);
    }

    $this->drawTitle($canvas, $title);

    return $canvas;
  }

  /**
   * Crée un canevas de secours sans couverture.
   *
   * @param string $title Titre de l'événement
   * @return GdImage Canevas final
   */
  private function createFallbackCanvas(string $title): GdImage
  {
    $canvas = $this->createBaseCanvas();
    $this->drawTitle($canvas, $title !== '' ? $title : 'Invitation Ken Luamba');

    return $canvas;
  }

  /**
   * Colle une couverture redimensionnée au centre d'un point donné.
   *
   * @param GdImage $canvas Canevas cible
   * @param GdImage $cover Image source
   * @param int|float $centerX Centre horizontal
   * @param int|float $centerY Centre vertical
   * @param int $maxWidth Largeur max
   * @param int $maxHeight Hauteur max
   * @return void
   */
  private function pasteResizedCover(
    GdImage $canvas,
    GdImage $cover,
    int|float $centerX,
    int|float $centerY,
    int $maxWidth,
    int $maxHeight,
  ): void {
    $sourceWidth = imagesx($cover);
    $sourceHeight = imagesy($cover);
    $ratio = min($maxWidth / $sourceWidth, $maxHeight / $sourceHeight);
    $targetWidth = (int) max(1, round($sourceWidth * $ratio));
    $targetHeight = (int) max(1, round($sourceHeight * $ratio));
    $resized = imagecreatetruecolor($targetWidth, $targetHeight);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    imagecopyresampled(
      $resized,
      $cover,
      0,
      0,
      0,
      0,
      $targetWidth,
      $targetHeight,
      $sourceWidth,
      $sourceHeight,
    );
    $destinationX = (int) round($centerX - ($targetWidth / 2));
    $destinationY = (int) round($centerY - ($targetHeight / 2));
    imagecopy($canvas, $resized, $destinationX, $destinationY, 0, 0, $targetWidth, $targetHeight);
    imagedestroy($resized);
  }

  /**
   * Calcule la disposition selon le nombre de couvertures.
   *
   * @param int $count Nombre de couvertures
   * @return array{width: int, height: int, columns: int, gap: int} Grille
   */
  private function computeLayout(int $count): array
  {
    if ($count === 2) {
      return ['width' => 220, 'height' => 330, 'columns' => 2, 'gap' => 22];
    }

    if ($count === 3) {
      return ['width' => 190, 'height' => 285, 'columns' => 3, 'gap' => 18];
    }

    return ['width' => 165, 'height' => 248, 'columns' => min(2, $count), 'gap' => 16];
  }

  /**
   * Encode un canevas en PNG.
   *
   * @param GdImage $canvas Canevas source
   * @return string Contenu binaire PNG
   */
  private function encodeCanvas(GdImage $canvas): string
  {
    ob_start();
    imagepng($canvas, null, 6);
    imagedestroy($canvas);
    $png = ob_get_clean();

    return is_string($png) ? $png : '';
  }

  /**
   * Résout une police TrueType système si disponible.
   *
   * @return string|null Chemin de police
   */
  private function resolveFontPath(): ?string
  {
    $candidates = [
      'C:\\Windows\\Fonts\\arial.ttf',
      '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
      '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
    ];

    foreach ($candidates as $candidate) {
      if (is_readable($candidate)) {
        return $candidate;
      }
    }

    return null;
  }

  /**
   * Tronque un texte pour l'affichage sur l'image.
   *
   * @param string $text Texte source
   * @param int $maxLength Longueur max
   * @return string Texte tronqué
   */
  private function truncateText(string $text, int $maxLength): string
  {
    if (strlen($text) <= $maxLength) {
      return $text;
    }

    return substr($text, 0, $maxLength - 1).'…';
  }
}
