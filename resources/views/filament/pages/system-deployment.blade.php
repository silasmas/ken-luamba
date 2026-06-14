<x-filament-panels::page>
  <div class="space-y-6 text-sm leading-relaxed">
    <section class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
      <h2 class="text-lg font-semibold">État du serveur</h2>
      <dl class="mt-4 grid gap-3 sm:grid-cols-2">
        <div>
          <dt class="text-gray-500 dark:text-gray-400">Environnement</dt>
          <dd class="font-medium text-gray-900 dark:text-gray-100">{{ config('app.env') }}</dd>
        </div>
        <div>
          <dt class="text-gray-500 dark:text-gray-400">URL application</dt>
          <dd class="font-medium text-gray-900 dark:text-gray-100">{{ config('app.url') }}</dd>
        </div>
        <div>
          <dt class="text-gray-500 dark:text-gray-400">Frontend</dt>
          <dd class="font-medium text-gray-900 dark:text-gray-100">{{ config('app.frontend_url') }}</dd>
        </div>
        <div>
          <dt class="text-gray-500 dark:text-gray-400">Lien public/storage</dt>
          <dd class="font-medium">
            @if ($storageLinked)
              <span class="text-success-600 dark:text-success-400">Actif</span>
            @else
              <span class="text-danger-600 dark:text-danger-400">Manquant — cliquez « Lien storage »</span>
            @endif
          </dd>
        </div>
      </dl>
    </section>

    <section class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
      <h2 class="text-lg font-semibold">Actions disponibles</h2>
      <p class="mt-2 text-gray-600 dark:text-gray-400">
        Utilisez les boutons en haut à droite de cette page. Réservé au rôle <strong>super_admin</strong>.
      </p>
      <ul class="mt-4 list-inside list-disc space-y-2 text-gray-700 dark:text-gray-300">
        <li><strong>Migrations</strong> — met à jour la structure de la base de données</li>
        <li><strong>Permissions Shield</strong> — génère les droits Filament + super admin</li>
        <li><strong>Lien storage</strong> — rend les images accessibles via <code>/storage</code></li>
        <li><strong>Seeders</strong> — données initiales (livres, admin, livreur…)</li>
        <li><strong>Setup complet</strong> — migrations + seeders + storage en une fois</li>
      </ul>
    </section>

    <section class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
      <h2 class="text-lg font-semibold">Déploiement HTTP (production Hostinger)</h2>
      <p class="mt-2 text-gray-600 dark:text-gray-400">
        Sans SSH, les mêmes actions sont disponibles via URL avec <code>DEPLOY_SECRET</code> :
      </p>
      <ul class="mt-3 space-y-1 font-mono text-xs text-gray-700 dark:text-gray-300">
        <li>{{ url('/?secret=VOTRE_SECRET') }} — migrate</li>
        <li>{{ url('/?secret=VOTRE_SECRET&action=shield') }} — shield</li>
        <li>{{ url('/?secret=VOTRE_SECRET&action=storage') }} — storage:link</li>
        <li>{{ url('/?secret=VOTRE_SECRET&action=setup') }} — setup complet</li>
      </ul>
    </section>

    @if ($lastAction && $lastOutput)
      <section class="rounded-xl border border-primary-200 bg-primary-50/40 p-5 dark:border-primary-800 dark:bg-primary-950/20">
        <h2 class="text-lg font-semibold">Dernière exécution : {{ $lastAction }}</h2>
        <pre class="mt-4 max-h-96 overflow-auto rounded-lg bg-gray-900 p-4 text-xs text-gray-100">{{ $lastOutput }}</pre>
      </section>
    @endif
  </div>
</x-filament-panels::page>
