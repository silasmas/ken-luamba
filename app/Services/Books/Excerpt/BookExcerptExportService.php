<?php

namespace App\Services\Books\Excerpt;

use App\Models\Book;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use RuntimeException;
use ZipArchive;

/**
 * Exporte l'extrait feuilletable d'un livre en PDF, Word ou EPUB.
 */
class BookExcerptExportService
{
  /**
   * Initialise le service avec le builder documentaire.
   *
   * @param BookExcerptDocumentBuilder $builder Préparation HTML et pages
   */
  public function __construct(
    private readonly BookExcerptDocumentBuilder $builder,
  ) {}

  /**
   * Génère un PDF d'extrait et retourne le chemin temporaire.
   *
   * @param Book $book Livre source
   * @param bool $includeCovers Inclure couverture et verso
   * @return string Chemin absolu du fichier
   */
  public function exportPdf(Book $book, bool $includeCovers): string
  {
    $html = $this->builder->buildHtmlDocument($book, $includeCovers);
    $outputPath = $this->temporaryPath($book, 'pdf');

    $dompdf = new Dompdf([
      'isRemoteEnabled' => true,
      'defaultFont' => 'DejaVu Sans',
      'chroot' => [storage_path('app/public'), base_path()],
    ]);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    File::put($outputPath, $dompdf->output());

    return $outputPath;
  }

  /**
   * Génère un document Word d'extrait et retourne le chemin temporaire.
   *
   * @param Book $book Livre source
   * @param bool $includeCovers Inclure couverture et verso
   * @return string Chemin absolu du fichier
   */
  public function exportDocx(Book $book, bool $includeCovers): string
  {
    if (! class_exists(PhpWord::class)) {
      throw new RuntimeException('PHPWord requis : composer require phpoffice/phpword');
    }

    $pages = $this->builder->resolvePages($book, $includeCovers);
    $phpWord = new PhpWord();
    $phpWord->setDefaultFontName('Calibri');
    $phpWord->setDefaultFontSize(11);
    $coverPath = $this->builder->resolveImagePath($book->cover_image);
    $backCoverPath = $this->builder->resolveImagePath($book->back_cover_image);
    $outputPath = $this->temporaryPath($book, 'docx');

    foreach ($pages as $index => $page) {
      $section = $phpWord->addSection();
      $this->appendDocxPage($section, $page, $book, $coverPath, $backCoverPath, $index + 1);

      if ($index < count($pages) - 1) {
        $section->addPageBreak();
      }
    }

    IOFactory::createWriter($phpWord, 'Word2007')->save($outputPath);

    return $outputPath;
  }

  /**
   * Génère un EPUB d'extrait et retourne le chemin temporaire.
   *
   * @param Book $book Livre source
   * @param bool $includeCovers Inclure couverture et verso
   * @return string Chemin absolu du fichier
   */
  public function exportEpub(Book $book, bool $includeCovers): string
  {
    if (! class_exists(ZipArchive::class)) {
      throw new RuntimeException('L\'extension PHP ZipArchive est requise pour l\'export EPUB.');
    }

    $pages = $this->builder->resolvePages($book, $includeCovers);
    $title = $book->title;
    $escapedTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $coverPath = $this->builder->resolveImagePath($book->cover_image);
    $outputPath = $this->temporaryPath($book, 'epub');
    $workDir = $this->temporaryDirectory($book).DIRECTORY_SEPARATOR.'epub';
    File::deleteDirectory($workDir);
    File::ensureDirectoryExists($workDir.'/META-INF');
    File::ensureDirectoryExists($workDir.'/OEBPS');

    $chapterFiles = [];
    $navItems = [];
    $manifestItems = [
      '<item id="ncx" href="toc.ncx" media-type="application/x-dtbncx+xml"/>',
      '<item id="style" href="style.css" media-type="text/css"/>',
    ];
    $spineItems = [];

    foreach ($pages as $index => $page) {
      $chapterId = 'chapter-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
      $fileName = $chapterId.'.xhtml';
      $chapterTitle = $this->pageTitle($page, $index + 1);
      $body = $this->builder->renderPageHtml(
        $page,
        $escapedTitle,
        $coverPath,
        $this->builder->resolveImagePath($book->back_cover_image),
        $index + 1,
      );
      $xhtml = $this->wrapEpubChapter($chapterTitle, $body);
      File::put($workDir.'/OEBPS/'.$fileName, $xhtml);
      $chapterFiles[] = $fileName;
      $manifestItems[] = '<item id="'.$chapterId.'" href="'.$fileName.'" media-type="application/xhtml+xml"/>';
      $spineItems[] = '<itemref idref="'.$chapterId.'"/>';
      $navItems[] = '<navPoint id="nav-'.$chapterId.'" playOrder="'.($index + 1).'">'
        .'<navLabel><text>'.htmlspecialchars($chapterTitle, ENT_QUOTES, 'UTF-8').'</text></navLabel>'
        .'<content src="'.$fileName.'"/></navPoint>';
    }

    $coverManifest = '';
    if ($coverPath !== null && $includeCovers) {
      $coverExtension = strtolower(pathinfo($coverPath, PATHINFO_EXTENSION));
      $coverFile = 'cover.'.($coverExtension !== '' ? $coverExtension : 'jpg');
      $coverMime = match ($coverExtension) {
        'png' => 'image/png',
        'webp' => 'image/webp',
        default => 'image/jpeg',
      };
      File::copy($coverPath, $workDir.'/OEBPS/'.$coverFile);
      $manifestItems[] = '<item id="cover-image" href="'.$coverFile.'" media-type="'.$coverMime.'" properties="cover-image"/>';
      $coverManifest = 'yes';
    }

    File::put($workDir.'/OEBPS/style.css', $this->builder->stylesheet());
    File::put($workDir.'/mimetype', 'application/epub+zip');
    File::put($workDir.'/META-INF/container.xml', <<<'XML'
<?xml version="1.0" encoding="UTF-8"?>
<container version="1.0" xmlns="urn:oasis:names:tc:opendocument:xmlns:container">
  <rootfiles>
    <rootfile full-path="OEBPS/content.opf" media-type="application/oebps-package+xml"/>
  </rootfiles>
</container>
XML);
    File::put(
      $workDir.'/OEBPS/content.opf',
      $this->buildOpf($escapedTitle, $book, implode("\n    ", $manifestItems), implode("\n    ", $spineItems), $coverManifest !== ''),
    );
    File::put(
      $workDir.'/OEBPS/toc.ncx',
      $this->buildNcx($escapedTitle, $book, implode("\n      ", $navItems), count($pages)),
    );

    $zip = new ZipArchive();
    if ($zip->open($outputPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
      throw new RuntimeException('Impossible de créer le fichier EPUB.');
    }

    $zip->addFile($workDir.'/mimetype', 'mimetype');
    $zip->setCompressionName('mimetype', ZipArchive::CM_STORE);
    $this->addDirectoryToZip($zip, $workDir, '');
    $zip->close();
    File::deleteDirectory($workDir);

    return $outputPath;
  }

  /**
   * Ajoute le contenu d'une page au document Word.
   *
   * @param mixed $section Section PHPWord
   * @param array<string, mixed> $page Page d'extrait
   * @param Book $book Livre source
   * @param string|null $coverPath Chemin couverture
   * @param string|null $backCoverPath Chemin verso
   * @param int $fallbackNumber Numéro de page
   * @return void
   */
  private function appendDocxPage(
    mixed $section,
    array $page,
    Book $book,
    ?string $coverPath,
    ?string $backCoverPath,
    int $fallbackNumber,
  ): void {
    $kind = (string) ($page['kind'] ?? 'text');

    if ($kind === 'cover' && $coverPath !== null) {
      $section->addImage($coverPath, ['width' => 420, 'height' => 595, 'alignment' => Jc::CENTER]);

      return;
    }

    if ($kind === 'backCover' && $backCoverPath !== null) {
      $section->addImage($backCoverPath, ['width' => 420, 'height' => 595, 'alignment' => Jc::CENTER]);

      return;
    }

    if ($kind === 'chapter') {
      $section->addText((string) ($page['chapter'] ?? ''), ['size' => 9, 'color' => '666666']);
      $section->addTextBreak();
      $section->addText((string) ($page['title'] ?? ''), ['bold' => true, 'size' => 20], ['alignment' => Jc::CENTER]);

      return;
    }

    if (filled($page['eyebrow'] ?? null)) {
      $section->addText(strtoupper((string) $page['eyebrow']), ['size' => 9, 'color' => '666666']);
      $section->addTextBreak();
    }

    if (filled($page['title'] ?? null)) {
      $section->addText((string) $page['title'], ['bold' => true, 'size' => 16]);
      $section->addTextBreak();
    }

    foreach ($page['paragraphs'] ?? [] as $paragraph) {
      $section->addText((string) $paragraph, ['size' => 11]);
      $section->addTextBreak();
    }

    $footerLeft = $kind === 'part' ? 'Parties du livre' : ($kind === 'section' ? 'Synthèse' : 'Ken Luamba');
    $pageLabel = (string) ($page['pageLabel'] ?? (string) $fallbackNumber);
    $section->addTextBreak(2);
    $section->addText($footerLeft.' · '.$pageLabel, ['size' => 8, 'color' => '999999']);
  }

  /**
   * Retourne un titre lisible pour une page d'extrait.
   *
   * @param array<string, mixed> $page Page source
   * @param int $fallbackNumber Numéro de secours
   * @return string Titre de chapitre EPUB
   */
  private function pageTitle(array $page, int $fallbackNumber): string
  {
    return match ((string) ($page['kind'] ?? 'text')) {
      'cover' => 'Couverture',
      'backCover' => 'Quatrième de couverture',
      'chapter' => (string) ($page['title'] ?? 'Chapitre'),
      default => (string) ($page['title'] ?? 'Page '.$fallbackNumber),
    };
  }

  /**
   * Emballe le fragment HTML d'une page pour l'EPUB.
   *
   * @param string $chapterTitle Titre de section
   * @param string $bodyHtml Corps HTML
   * @return string Document XHTML
   */
  private function wrapEpubChapter(string $chapterTitle, string $bodyHtml): string
  {
    return '<?xml version="1.0" encoding="UTF-8"?>'
      .'<html xmlns="http://www.w3.org/1999/xhtml"><head>'
      .'<title>'.htmlspecialchars($chapterTitle, ENT_QUOTES, 'UTF-8').'</title>'
      .'<link rel="stylesheet" type="text/css" href="style.css"/>'
      .'</head><body>'.$bodyHtml.'</body></html>';
  }

  /**
   * Construit le manifeste OPF de l'EPUB.
   *
   * @param string $escapedTitle Titre échappé
   * @param Book $book Livre source
   * @param string $manifestItems Items du manifeste
   * @param string $spineItems Items de la spine
   * @param bool $hasCoverImage Présence d'une image de couverture
   * @return string XML OPF
   */
  private function buildOpf(
    string $escapedTitle,
    Book $book,
    string $manifestItems,
    string $spineItems,
    bool $hasCoverImage,
  ): string {
    $uuid = Str::uuid()->toString();
    $coverMeta = $hasCoverImage
      ? '<meta name="cover" content="cover-image"/>'
      : '';

    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<package xmlns="http://www.idpf.org/2007/opf" version="2.0" unique-identifier="BookId">
  <metadata xmlns:dc="http://purl.org/dc/elements/1.1/">
    <dc:title>{$escapedTitle} — Extrait</dc:title>
    <dc:creator>Ken Luamba</dc:creator>
    <dc:language>fr</dc:language>
    <dc:identifier id="BookId">urn:uuid:{$uuid}</dc:identifier>
    {$coverMeta}
  </metadata>
  <manifest>
    {$manifestItems}
  </manifest>
  <spine toc="ncx">
    {$spineItems}
  </spine>
</package>
XML;
  }

  /**
   * Construit la table des matières NCX de l'EPUB.
   *
   * @param string $escapedTitle Titre échappé
   * @param Book $book Livre source
   * @param string $navItems Points de navigation
   * @param int $pageCount Nombre de pages
   * @return string XML NCX
   */
  private function buildNcx(string $escapedTitle, Book $book, string $navItems, int $pageCount): string
  {
    $uuid = Str::uuid()->toString();

    return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<ncx xmlns="http://www.daisy.org/z3986/2005/ncx/" version="2005-1">
  <head>
    <meta name="dtb:uid" content="urn:uuid:{$uuid}"/>
    <meta name="dtb:depth" content="1"/>
    <meta name="dtb:totalPageCount" content="{$pageCount}"/>
    <meta name="dtb:maxPageNumber" content="{$pageCount}"/>
  </head>
  <docTitle><text>{$escapedTitle} — Extrait</text></docTitle>
  <navMap>
      {$navItems}
  </navMap>
</ncx>
XML;
  }

  /**
   * Ajoute récursivement un dossier dans une archive ZIP.
   *
   * @param ZipArchive $zip Archive cible
   * @param string $sourceDir Dossier source
   * @param string $zipPrefix Préfixe dans l'archive
   * @return void
   */
  private function addDirectoryToZip(ZipArchive $zip, string $sourceDir, string $zipPrefix): void
  {
    foreach (File::allFiles($sourceDir) as $filePath) {
      $relative = str_replace('\\', '/', substr((string) $filePath, strlen($sourceDir) + 1));

      if ($relative === 'mimetype') {
        continue;
      }

      $zip->addFile((string) $filePath, ltrim($zipPrefix.'/'.$relative, '/'));
    }
  }

  /**
   * Retourne un dossier temporaire pour les exports d'un livre.
   *
   * @param Book $book Livre source
   * @return string Chemin absolu
   */
  private function temporaryDirectory(Book $book): string
  {
    $directory = storage_path('app/exports/excerpts/'.$book->slug);
    File::ensureDirectoryExists($directory);

    return $directory;
  }

  /**
   * Retourne un chemin temporaire de fichier exporté.
   *
   * @param Book $book Livre source
   * @param string $extension Extension sans point
   * @return string Chemin absolu
   */
  private function temporaryPath(Book $book, string $extension): string
  {
    return $this->temporaryDirectory($book).DIRECTORY_SEPARATOR.$book->slug.'-extrait.'.$extension;
  }
}
