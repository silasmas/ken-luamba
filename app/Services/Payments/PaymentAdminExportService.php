<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Support\OrderAdminFormatter;
use Illuminate\Database\Eloquent\Collection;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use RuntimeException;

/**
 * Exporte les paiements admin filtrés en Excel et PDF.
 */
class PaymentAdminExportService
{
  /**
   * Génère un fichier Excel des paiements.
   *
   * @param Collection<int, Payment> $payments Paiements exportés
   * @return string Chemin absolu du fichier généré
   */
  public function exportExcel(Collection $payments): string
  {
    $directory = storage_path('app/exports');

    if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
      throw new RuntimeException('Impossible de créer le dossier d\'export.');
    }

    $path = $directory.DIRECTORY_SEPARATOR.'paiements-'.now()->format('Ymd-His').'.xlsx';

    $writer = new Writer();
    $writer->openToFile($path);
    $writer->getCurrentSheet()->setName('Paiements');
    $writer->addRow(Row::fromValues([
      'N° commande',
      'Client payeur',
      'Email',
      'Téléphone',
      'Articles',
      'Réf. FlexPay',
      'Canal',
      'Montant',
      'Devise',
      'Statut paiement',
      'Payé le',
      'Livre reçu',
      'Mode d\'achat',
      'Soutien volontaire',
      'Créé le',
    ]));

    foreach ($payments as $payment) {
      $writer->addRow(Row::fromValues($this->rowValues($payment)));
    }

    $writer->close();

    return $path;
  }

  /**
   * Génère un fichier PDF des paiements.
   *
   * @param Collection<int, Payment> $payments Paiements exportés
   * @return string Chemin absolu du fichier généré
   */
  public function exportPdf(Collection $payments): string
  {
    if (! class_exists(\Dompdf\Dompdf::class)) {
      throw new RuntimeException('Dompdf requis : composer require dompdf/dompdf');
    }

    $directory = storage_path('app/exports');

    if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
      throw new RuntimeException('Impossible de créer le dossier d\'export.');
    }

    $path = $directory.DIRECTORY_SEPARATOR.'paiements-'.now()->format('Ymd-His').'.pdf';

    $html = view('exports.payments', [
      'title' => 'Export des paiements',
      'generatedAt' => now()->timezone(config('app.timezone'))->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH[h]mm'),
      'payments' => $payments,
      'rows' => $payments->map(fn (Payment $payment): array => $this->rowValues($payment))->all(),
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
   * Transforme un paiement en ligne tabulaire.
   *
   * @param Payment $payment Paiement exporté
   * @return list<string|float|null> Valeurs de colonnes
   */
  private function rowValues(Payment $payment): array
  {
    $payment->loadMissing(['order.user', 'order.items', 'order.delivery']);
    $order = $payment->order;

    return [
      (string) ($order?->order_number ?? ''),
      (string) ($order?->user?->full_name ?? ''),
      (string) ($order?->user?->email ?? ''),
      (string) ($order?->user?->phone ?? ($order?->shipping_address['phone'] ?? '')),
      $order ? OrderAdminFormatter::itemsSummary($order) : '—',
      (string) ($payment->provider_reference ?? ''),
      (string) ($payment->channel?->label() ?? '—'),
      (float) $payment->amount,
      (string) $payment->currency,
      (string) $payment->status->label(),
      $payment->paid_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '',
      $order ? OrderAdminFormatter::booksReceivedLabel($order) : '—',
      $order ? OrderAdminFormatter::paymentModeLabel($order) : '—',
      $order ? OrderAdminFormatter::extraContributionAmount($order) : 0,
      $payment->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?? '',
    ];
  }
}
