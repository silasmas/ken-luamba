<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Support\OrderAdminFormatter;
use Illuminate\Database\Eloquent\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

/**
 * Exporte les commandes admin filtrées en Excel et PDF.
 */
class OrderAdminExportService
{
  /**
   * Génère un fichier Excel des commandes.
   *
   * @param Collection<int, Order> $orders Commandes exportées
   * @return string Chemin absolu du fichier généré
   */
  public function exportExcel(Collection $orders): string
  {
    $directory = storage_path('app/exports');

    if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
      throw new RuntimeException('Impossible de créer le dossier d\'export.');
    }

    $path = $directory.DIRECTORY_SEPARATOR.'commandes-'.now()->format('Ymd-His').'.xlsx';

    $writer = new Writer();
    $writer->openToFile($path);
    $writer->getCurrentSheet()->setName('Commandes');
    $writer->addRow(Row::fromValues([
      'N° commande',
      'Client',
      'Email',
      'Téléphone',
      'Articles',
      'Statut commande',
      'Payée le',
      'Mode réception',
      'Livre reçu',
      'Sous-total',
      'Remise',
      'Livraison',
      'Soutien volontaire',
      'Total',
      'Devise',
      'Créée le',
    ]));

    foreach ($orders as $order) {
      $writer->addRow(Row::fromValues($this->rowValues($order)));
    }

    $writer->close();

    return $path;
  }

  /**
   * Génère un fichier PDF des commandes.
   *
   * @param Collection<int, Order> $orders Commandes exportées
   * @return string Chemin absolu du fichier généré
   */
  public function exportPdf(Collection $orders): string
  {
    if (! class_exists(\Dompdf\Dompdf::class)) {
      throw new RuntimeException('Dompdf requis : composer require dompdf/dompdf');
    }

    $directory = storage_path('app/exports');

    if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
      throw new RuntimeException('Impossible de créer le dossier d\'export.');
    }

    $path = $directory.DIRECTORY_SEPARATOR.'commandes-'.now()->format('Ymd-His').'.pdf';

    $html = view('exports.orders', [
      'title' => 'Export des commandes',
      'generatedAt' => now()->timezone(config('app.timezone'))->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH[h]mm'),
      'orders' => $orders,
      'rows' => $orders->map(fn (Order $order): array => $this->rowValues($order))->all(),
    ])->render();

    $dompdf = new \Dompdf\Dompdf([
      'isRemoteEnabled' => false,
      'isHtml5ParserEnabled' => true,
      'defaultFont' => 'DejaVu Sans',
    ]);
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    file_put_contents($path, $dompdf->output());

    return $path;
  }

  /**
   * Transforme une commande en ligne tabulaire.
   *
   * @param Order $order Commande exportée
   * @return list<string|float|null> Valeurs de colonnes
   */
  private function rowValues(Order $order): array
  {
    $order->loadMissing(['user', 'items', 'delivery']);

    return [
      (string) $order->order_number,
      (string) ($order->user?->full_name ?? ''),
      (string) ($order->user?->email ?? ''),
      (string) ($order->user?->phone ?? ($order->shipping_address['phone'] ?? '')),
      OrderAdminFormatter::itemsSummary($order),
      (string) $order->status->label(),
      $order->paid_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '',
      (string) ($order->fulfillment_type?->label() ?? '—'),
      OrderAdminFormatter::booksReceivedLabel($order),
      (float) $order->subtotal,
      (float) $order->discount_amount,
      (float) $order->shipping_amount,
      (float) ($order->extra_contribution_amount ?? 0),
      (float) $order->total,
      (string) $order->currency,
      $order->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '',
    ];
  }
}
