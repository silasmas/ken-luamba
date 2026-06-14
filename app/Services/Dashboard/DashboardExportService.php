<?php

namespace App\Services\Dashboard;

use Carbon\CarbonInterface;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

/**
 * Exporte les données du tableau de bord admin au format Excel (.xlsx).
 */
class DashboardExportService
{
  /**
   * Initialise le service d'export.
   *
   * @param DashboardAnalyticsService $analyticsService Agrégateur de métriques
   */
  public function __construct(
    private readonly DashboardAnalyticsService $analyticsService,
  ) {}

  /**
   * Génère un fichier Excel contenant résumé, données et séries des graphiques.
   *
   * @param array<string, mixed>|null $filters Filtres du dashboard
   * @return string Chemin absolu du fichier généré
   */
  public function export(?array $filters): string
  {
    $period = $this->analyticsService->resolvePeriod($filters);
    $start = $period['start'];
    $end = $period['end'];

    $directory = storage_path('app/exports');

    if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
      throw new RuntimeException('Impossible de créer le dossier d\'export.');
    }

    $filename = 'dashboard-'.$start->format('Ymd').'-'.$end->format('Ymd').'.xlsx';
    $path = $directory.DIRECTORY_SEPARATOR.$filename;

    $writer = new Writer();
    $writer->openToFile($path);

    $this->writeSummarySheet($writer, $start, $end);
    $this->writeKeyValueSheet($writer, 'Formats livres', $this->analyticsService->bookFormatsByType());
    $this->writeTrendSheet($writer, 'Graphique ventes', 'Date', 'Chiffre affaires (CDF)', $this->analyticsService->salesTrend($start, $end));
    $this->writeTrendSheet($writer, 'Graphique clients', 'Date', 'Nouveaux clients', $this->analyticsService->clientsTrend($start, $end));
    $this->writeTrendSheet($writer, 'Graphique achats', 'Date', 'Quantité achetée', $this->analyticsService->purchasesTrend($start, $end));
    $this->writeKeyValueSheet($writer, 'Commandes par statut', $this->analyticsService->ordersByStatus($start, $end));
    $this->writeOrdersDetailSheet($writer, $start, $end);

    $writer->close();

    return $path;
  }

  /**
   * Écrit la feuille de synthèse.
   *
   * @param Writer $writer Writer OpenSpout
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return void
   */
  private function writeSummarySheet(Writer $writer, CarbonInterface $start, CarbonInterface $end): void
  {
    $writer->getCurrentSheet()->setName('Résumé');

    $rows = [
      ['Indicateur', 'Valeur'],
      ['Période du', $start->format('d/m/Y')],
      ['Période au', $end->format('d/m/Y')],
      ['Clients (total)', (string) $this->analyticsService->totalClients()],
      ['Nouveaux clients (période)', (string) $this->analyticsService->newClientsInPeriod($start, $end)],
      ['Livres (total)', (string) $this->analyticsService->totalBooks()],
      ['Livres publiés', (string) $this->analyticsService->publishedBooks()],
      ['Chiffre d\'affaires (période)', number_format($this->analyticsService->revenueInPeriod($start, $end), 0, ',', ' ').' CDF'],
      ['Commandes (période)', (string) $this->analyticsService->ordersInPeriod($start, $end)],
      ['Achats / quantités (période)', (string) $this->analyticsService->purchasesInPeriod($start, $end)],
    ];

    foreach ($rows as $row) {
      $writer->addRow(Row::fromValues($row));
    }
  }

  /**
   * Écrit une feuille clé/valeur.
   *
   * @param Writer $writer Writer OpenSpout
   * @param string $sheetName Nom de l'onglet
   * @param array<string, int|float|string> $data Données à exporter
   * @return void
   */
  private function writeKeyValueSheet(Writer $writer, string $sheetName, array $data): void
  {
    $writer->addNewSheetAndMakeItCurrent();
    $writer->getCurrentSheet()->setName($this->sanitizeSheetName($sheetName));
    $writer->addRow(Row::fromValues(['Libellé', 'Valeur']));

    foreach ($data as $label => $value) {
      $writer->addRow(Row::fromValues([(string) $label, (string) $value]));
    }
  }

  /**
   * Écrit une feuille de série temporelle.
   *
   * @param Writer $writer Writer OpenSpout
   * @param string $sheetName Nom de l'onglet
   * @param string $labelHeader En-tête colonne labels
   * @param string $valueHeader En-tête colonne valeurs
   * @param array{labels: list<string>, values: list<float|int>} $series Série à exporter
   * @return void
   */
  private function writeTrendSheet(
    Writer $writer,
    string $sheetName,
    string $labelHeader,
    string $valueHeader,
    array $series,
  ): void {
    $writer->addNewSheetAndMakeItCurrent();
    $writer->getCurrentSheet()->setName($this->sanitizeSheetName($sheetName));
    $writer->addRow(Row::fromValues([$labelHeader, $valueHeader]));

    foreach ($series['labels'] as $index => $label) {
      $writer->addRow(Row::fromValues([
        (string) $label,
        (string) ($series['values'][$index] ?? 0),
      ]));
    }
  }

  /**
   * Écrit le détail des commandes de la période.
   *
   * @param Writer $writer Writer OpenSpout
   * @param CarbonInterface $start Début de période
   * @param CarbonInterface $end Fin de période
   * @return void
   */
  private function writeOrdersDetailSheet(Writer $writer, CarbonInterface $start, CarbonInterface $end): void
  {
    $writer->addNewSheetAndMakeItCurrent();
    $writer->getCurrentSheet()->setName('Détail commandes');
    $writer->addRow(Row::fromValues([
      'N° commande',
      'Date',
      'Statut',
      'Client',
      'Total',
      'Devise',
    ]));

    $orders = \App\Models\Order::query()
      ->with('user:id,full_name,email')
      ->whereBetween('created_at', [$start, $end])
      ->orderBy('created_at')
      ->get();

    foreach ($orders as $order) {
      $writer->addRow(Row::fromValues([
        (string) $order->order_number,
        $order->created_at?->format('d/m/Y H:i') ?? '',
        $order->status->label(),
        (string) ($order->user?->full_name ?? $order->user?->email ?? '—'),
        (string) $order->total,
        (string) $order->currency,
      ]));
    }
  }

  /**
   * Nettoie un nom d'onglet Excel (31 caractères max, caractères interdits).
   *
   * @param string $name Nom brut
   * @return string Nom valide
   */
  private function sanitizeSheetName(string $name): string
  {
    $sanitized = preg_replace('/[\\\\\\/\\?\\*\\[\\]:]/', '-', $name) ?? $name;

    return mb_substr($sanitized, 0, 31);
  }
}
