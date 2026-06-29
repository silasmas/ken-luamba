<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>{{ $title }}</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 10px;
      color: #111827;
      margin: 24px;
    }

    h1 {
      font-size: 18px;
      margin: 0 0 4px;
    }

    .meta {
      color: #4b5563;
      margin-bottom: 16px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      border: 1px solid #d1d5db;
      padding: 5px 6px;
      vertical-align: top;
      text-align: left;
    }

    th {
      background: #f3f4f6;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <h1>{{ $title }}</h1>
  <p class="meta">Généré le {{ $generatedAt }} — {{ count($rows) }} paiement(s)</p>

  @if (count($rows) === 0)
    <p>Aucun paiement pour ce filtre.</p>
  @else
    <table>
      <thead>
        <tr>
          <th>Commande</th>
          <th>Client payeur</th>
          <th>Email</th>
          <th>Articles</th>
          <th>Réf. FlexPay</th>
          <th>Canal</th>
          <th>Montant</th>
          <th>Devise</th>
          <th>Statut</th>
          <th>Payé le</th>
          <th>Livre reçu</th>
          <th>Mode d'achat</th>
          <th>Soutien</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($rows as $row)
          <tr>
            <td>{{ $row[0] }}</td>
            <td>{{ $row[1] }}</td>
            <td>{{ $row[2] }}</td>
            <td>{{ $row[4] }}</td>
            <td>{{ $row[5] }}</td>
            <td>{{ $row[6] }}</td>
            <td>{{ number_format((float) $row[7], 2, ',', ' ') }}</td>
            <td>{{ $row[8] }}</td>
            <td>{{ $row[9] }}</td>
            <td>{{ $row[10] }}</td>
            <td>{{ $row[11] }}</td>
            <td>{{ $row[12] }}</td>
            <td>{{ number_format((float) $row[13], 2, ',', ' ') }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</body>
</html>
