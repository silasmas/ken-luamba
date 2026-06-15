<!DOCTYPE html>
<html lang="fr">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Ken Luamba — Administration</title>
    <style>
      :root {
        color-scheme: light;
        --ink: #0a0a0a;
        --paper: #f7f4ef;
        --accent: #2563eb;
        --muted: rgba(10, 10, 10, 0.58);
      }

      * {
        box-sizing: border-box;
      }

      body {
        margin: 0;
        min-height: 100vh;
        display: grid;
        place-items: center;
        background:
          radial-gradient(circle at top left, rgba(37, 99, 235, 0.12), transparent 32rem),
          linear-gradient(180deg, #ffffff 0%, var(--paper) 100%);
        font-family: Inter, Segoe UI, sans-serif;
        color: var(--ink);
      }

      .card {
        width: min(92vw, 34rem);
        padding: 2.5rem 2rem;
        border: 1px solid rgba(10, 10, 10, 0.08);
        border-radius: 1.75rem;
        background: rgba(255, 255, 255, 0.88);
        box-shadow: 0 30px 80px -40px rgba(10, 10, 10, 0.35);
        text-align: center;
      }

      .eyebrow {
        display: inline-block;
        margin-bottom: 1rem;
        font-size: 0.72rem;
        letter-spacing: 0.24em;
        text-transform: uppercase;
        color: var(--muted);
      }

      h1 {
        margin: 0;
        font-size: clamp(2rem, 5vw, 2.6rem);
        line-height: 1.05;
      }

      p {
        margin: 1rem 0 0;
        color: var(--muted);
        line-height: 1.6;
      }

      .actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        justify-content: center;
        margin-top: 2rem;
      }

      .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 3rem;
        padding: 0 1.4rem;
        border-radius: 999px;
        font-weight: 600;
        text-decoration: none;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
      }

      .btn-primary {
        background: var(--accent);
        color: #fff;
        box-shadow: 0 16px 40px -20px rgba(37, 99, 235, 0.8);
      }

      .btn-secondary {
        border: 1px solid rgba(10, 10, 10, 0.12);
        color: var(--ink);
        background: #fff;
      }

      .btn:hover {
        transform: translateY(-1px);
      }

      .footer {
        margin-top: 1.5rem;
        font-size: 0.82rem;
        color: rgba(10, 10, 10, 0.45);
      }
    </style>
  </head>
  <body>
    <main class="card">
      <span class="eyebrow">Ken Luamba Éditions</span>
      <h1>Portail d'administration</h1>
      <p>
        Bienvenue sur l'espace de gestion de la boutique, du catalogue et des communications.
        Connectez-vous pour accéder au tableau de bord Filament.
      </p>

      <div class="actions">
        <a class="btn btn-primary" href="{{ $adminLoginUrl }}">Accéder à la connexion admin</a>
        @if ($frontendUrl)
          <a class="btn btn-secondary" href="{{ $frontendUrl }}" target="_blank" rel="noopener noreferrer">Voir le site public</a>
        @endif
      </div>

      <p class="footer">API opérationnelle · Service Ken Luamba</p>
    </main>
  </body>
</html>
