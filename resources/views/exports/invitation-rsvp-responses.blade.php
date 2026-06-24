<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>{{ $title }}</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 11px;
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

    .summary {
      margin-bottom: 16px;
    }

    .summary span {
      display: inline-block;
      margin-right: 16px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th, td {
      border: 1px solid #d1d5db;
      padding: 6px 8px;
      vertical-align: top;
      text-align: left;
    }

    th {
      background: #f3f4f6;
      font-weight: bold;
    }

    .status-present {
      color: #15803d;
      font-weight: bold;
    }

    .status-absent {
      color: #b91c1c;
      font-weight: bold;
    }

    .comment {
      max-width: 220px;
      word-wrap: break-word;
    }
  </style>
</head>
<body>
  <h1>{{ $title }}</h1>
  <p class="meta">
    Événement : <strong>{{ $eventLabel }}</strong><br>
    Généré le {{ $generatedAt }}
  </p>

  <div class="summary">
    <span><strong>Présents :</strong> {{ $stats['attending'] }}</span>
    <span><strong>Absents :</strong> {{ $stats['notAttending'] }}</span>
    <span><strong>Total réponses :</strong> {{ $stats['responded'] }}</span>
  </div>

  @if ($invitations->isEmpty())
    <p>Aucune réponse enregistrée pour ce filtre.</p>
  @else
    <table>
      <thead>
        <tr>
          <th>Nom</th>
          <th>Email</th>
          <th>Téléphone</th>
          @if ($includeEventColumn)
            <th>Événement</th>
          @endif
          <th>Statut</th>
          <th>Date réponse</th>
          <th>Commentaire</th>
        </tr>
      </thead>
      <tbody>
        @foreach ($invitations as $invitation)
          <tr>
            <td>{{ $invitation->full_name }}</td>
            <td>{{ $invitation->email ?: '—' }}</td>
            <td>{{ $invitation->phone ?: '—' }}</td>
            @if ($includeEventColumn)
              <td>{{ $invitation->event?->title ?: '—' }}</td>
            @endif
            <td class="{{ $invitation->rsvp_status?->value === 'attending' ? 'status-present' : 'status-absent' }}">
              {{ $invitation->rsvp_status?->label() }}
            </td>
            <td>
              {{ $invitation->responded_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') ?: '—' }}
            </td>
            <td class="comment">{{ $invitation->guest_message ?: '—' }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
  @endif
</body>
</html>
