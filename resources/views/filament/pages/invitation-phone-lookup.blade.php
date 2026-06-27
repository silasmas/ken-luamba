<x-filament-panels::page>
  <div class="space-y-6">
    <section class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
      <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Liste de numéros</h2>
      <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
        Collez les numéros des invités (un par ligne, ou séparés par des virgules). Les espaces, tirets,
        parenthèses et le signe + seront ignorés pour la recherche.
      </p>

      <div class="mt-4 grid gap-4 lg:grid-cols-[2fr_1fr]">
        <div>
          <label for="phonesRaw" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Numéros à rechercher
          </label>
          <textarea
            id="phonesRaw"
            wire:model.defer="phonesRaw"
            rows="10"
            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
            placeholder="+243 812 345 678&#10;0812-345-678&#10;243998877665"
          ></textarea>
        </div>

        <div>
          <label for="eventId" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">
            Limiter à un événement (optionnel)
          </label>
          <select
            id="eventId"
            wire:model.defer="eventId"
            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
          >
            <option value="">Tous les événements</option>
            @foreach ($this->eventOptions as $id => $title)
              <option value="{{ $id }}">{{ $title }}</option>
            @endforeach
          </select>

          <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
            Cliquez sur <strong>Analyser les numéros</strong> en haut à droite, puis filtrez ou téléchargez
            le tableau ci-dessous.
          </p>
        </div>
      </div>
    </section>

    @if ($stats['total'] > 0)
      <section class="grid gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-700">
          <p class="text-xs uppercase tracking-wide text-gray-500">Analysés</p>
          <p class="mt-1 text-2xl font-semibold text-gray-950 dark:text-white">{{ $stats['total'] }}</p>
        </div>
        <div class="rounded-xl border border-success-200 bg-success-50/60 p-4 dark:border-success-800 dark:bg-success-950/20">
          <p class="text-xs uppercase tracking-wide text-success-700 dark:text-success-300">Correspondances</p>
          <p class="mt-1 text-2xl font-semibold text-success-800 dark:text-success-200">{{ $stats['matched'] }}</p>
        </div>
        <div class="rounded-xl border border-danger-200 bg-danger-50/60 p-4 dark:border-danger-800 dark:bg-danger-950/20">
          <p class="text-xs uppercase tracking-wide text-danger-700 dark:text-danger-300">Sans nom trouvé</p>
          <p class="mt-1 text-2xl font-semibold text-danger-800 dark:text-danger-200">{{ $stats['unmatched'] }}</p>
        </div>
      </section>
    @endif

    @if ($results !== [])
      <section class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
          <h2 class="text-lg font-semibold text-gray-950 dark:text-white">Résultats</h2>
          <div class="flex flex-wrap items-center gap-2">
            <label for="statusFilter" class="text-sm text-gray-600 dark:text-gray-400">Statut</label>
            <select
              id="statusFilter"
              wire:model.live="statusFilter"
              class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
            >
              @foreach ($this->statusFilterOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="mt-4 overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-900/40">
              <tr>
                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Numéro saisi</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Normalisé</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Correspondance</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Statut RSVP</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Nom invité</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Email</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Événement</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600 dark:text-gray-300">Lien invitation</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
              @forelse ($this->filteredResults as $row)
                <tr @class([
                  'bg-danger-50/40 dark:bg-danger-950/10' => ! $row['matched'],
                ])>
                  <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $row['input'] }}</td>
                  <td class="px-3 py-2 font-mono text-xs text-gray-700 dark:text-gray-300">{{ $row['normalized'] ?: '—' }}</td>
                  <td class="px-3 py-2">
                    @if ($row['matched'])
                      <span class="inline-flex rounded-full bg-success-100 px-2 py-0.5 text-xs font-medium text-success-800 dark:bg-success-900/40 dark:text-success-200">
                        {{ $row['statusLabel'] }}
                      </span>
                    @else
                      <span class="inline-flex rounded-full bg-danger-100 px-2 py-0.5 text-xs font-medium text-danger-800 dark:bg-danger-900/40 dark:text-danger-200">
                        {{ $row['statusLabel'] }}
                      </span>
                    @endif
                  </td>
                  <td class="px-3 py-2">
                    @if ($row['rsvpStatusLabel'])
                      <span @class([
                        'inline-flex rounded-full px-2 py-0.5 text-xs font-medium',
                        'bg-warning-100 text-warning-800 dark:bg-warning-900/40 dark:text-warning-200' => ($row['rsvpStatus'] ?? '') === 'pending',
                        'bg-success-100 text-success-800 dark:bg-success-900/40 dark:text-success-200' => ($row['rsvpStatus'] ?? '') === 'attending',
                        'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200' => ($row['rsvpStatus'] ?? '') === 'not_attending',
                      ])>
                        {{ $row['rsvpStatusLabel'] }}
                      </span>
                    @else
                      <span class="text-gray-400">—</span>
                    @endif
                  </td>
                  <td class="px-3 py-2 text-gray-900 dark:text-gray-100">{{ $row['fullName'] ?: '—' }}</td>
                  <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $row['email'] ?: '—' }}</td>
                  <td class="px-3 py-2 text-gray-700 dark:text-gray-300">{{ $row['eventTitle'] ?: '—' }}</td>
                  <td class="px-3 py-2">
                    @if ($row['invitationLink'])
                      <a
                        href="{{ $row['invitationLink'] }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="break-all text-primary-600 hover:underline dark:text-primary-400"
                      >
                        {{ $row['invitationLink'] }}
                      </a>
                    @else
                      <span class="text-gray-400">—</span>
                    @endif
                  </td>
                </tr>
              @empty
                <tr>
                  <td colspan="8" class="px-3 py-6 text-center text-gray-500 dark:text-gray-400">
                    Aucune ligne pour ce filtre.
                  </td>
                </tr>
              @endforelse
            </tbody>
          </table>
        </div>
      </section>
    @endif
  </div>
</x-filament-panels::page>
