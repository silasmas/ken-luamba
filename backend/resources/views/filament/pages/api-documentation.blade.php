<x-filament-panels::page>
  <div class="space-y-6 text-sm leading-relaxed">
    <p class="text-gray-600 dark:text-gray-400">
      Base URL : <code class="rounded bg-gray-100 px-2 py-1 dark:bg-gray-800">{{ url('/api/v1') }}</code>
    </p>

    <section class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
      <h2 class="text-lg font-semibold">Catalogue (public)</h2>
      <ul class="mt-3 list-inside list-disc space-y-1 text-gray-700 dark:text-gray-300">
        <li><code>GET /health</code> — Santé API</li>
        <li><code>GET /books</code> — Liste des livres</li>
        <li><code>GET /books/{slug}</code> — Détail livre</li>
        <li><code>GET /authors/{slug}</code> — Profil auteur</li>
        <li><code>GET /pickup-points</code> — Points de retrait actifs</li>
      </ul>
    </section>

    <section class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
      <h2 class="text-lg font-semibold">Panier</h2>
      <p class="mt-2 text-gray-600 dark:text-gray-400">Header invité : <code>X-Cart-Session</code></p>
      <ul class="mt-3 list-inside list-disc space-y-1 text-gray-700 dark:text-gray-300">
        <li><code>POST /cart/session</code> — Créer session panier</li>
        <li><code>GET /cart</code> — Consulter le panier</li>
        <li><code>POST /cart/items</code> — Ajouter un article</li>
        <li><code>PATCH /cart/items/{id}</code> — Modifier quantité</li>
        <li><code>DELETE /cart/items/{id}</code> — Retirer un article</li>
      </ul>
    </section>

    <section class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
      <h2 class="text-lg font-semibold">Authentification OTP</h2>
      <ul class="mt-3 list-inside list-disc space-y-1 text-gray-700 dark:text-gray-300">
        <li><code>POST /auth/register</code> — Inscription (envoi OTP)</li>
        <li><code>POST /auth/login</code> — Connexion (envoi OTP)</li>
        <li><code>POST /auth/verify-otp</code> — Vérification → token Sanctum</li>
        <li><code>GET /auth/me</code> — Profil (Bearer token)</li>
        <li><code>POST /auth/logout</code> — Déconnexion</li>
      </ul>
    </section>

    <section class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
      <h2 class="text-lg font-semibold">Commandes &amp; paiements (auth requise)</h2>
      <ul class="mt-3 list-inside list-disc space-y-1 text-gray-700 dark:text-gray-300">
        <li><code>POST /orders</code> — Créer commande depuis panier</li>
        <li><code>GET /orders</code> — Mes commandes</li>
        <li><code>GET /orders/{orderNumber}</code> — Détail commande</li>
        <li><code>POST /orders/{orderNumber}/pay</code> — Payer (mobile_money | card)</li>
        <li><code>GET /payments/status?reference=</code> — Polling statut FlexPay</li>
        <li><code>GET /payments/card-return?reference=&amp;status=</code> — Retour carte</li>
        <li><code>POST /payments/flexpay-callback</code> — Webhook FlexPay</li>
      </ul>
      <p class="mt-3 text-amber-700 dark:text-amber-400">
        Mobile Money : FlexPay <code>type = "1"</code> (voir docs/integration-paiement-flexpay).
      </p>
    </section>
    <section class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
      <h2 class="text-lg font-semibold">Rôles &amp; permissions (Filament Shield)</h2>
      <p class="mt-2 text-gray-600 dark:text-gray-400">
        Menu <strong>Rôles</strong> : créez des rôles et cochez les permissions par ressource.
        Menu <strong>Utilisateurs</strong> : assignez rôle métier + rôles Shield.
      </p>
      <ul class="mt-3 list-inside list-disc space-y-1 text-gray-700 dark:text-gray-300">
        <li><code>super_admin</code> — accès total (admin@kenluamba.com)</li>
        <li>Permissions par ressource : view, create, update, delete…</li>
        <li>Commande : <code>php artisan shield:generate --all --panel=admin</code></li>
      </ul>
    </section>
  </div>
</x-filament-panels::page>
