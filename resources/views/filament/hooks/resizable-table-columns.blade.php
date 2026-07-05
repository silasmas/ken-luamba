<style>
  .fi-ta-content {
    overflow-x: auto;
  }

  .kl-resizable-th {
    position: relative;
  }

  .kl-col-resize-handle {
    position: absolute;
    top: 0;
    right: 0;
    z-index: 2;
    width: 8px;
    height: 100%;
    cursor: col-resize;
    touch-action: none;
    user-select: none;
  }

  .kl-col-resizable-active {
    cursor: col-resize !important;
    user-select: none !important;
  }

  .kl-order-items,
  .kl-books-received {
    max-width: 100%;
  }

  .kl-order-items {
    overflow-x: auto;
  }
</style>

<script>
  (() => {
    const storageKey = 'kl-admin-column-widths';

    /**
     * Applique une largeur mémorisée à une colonne du tableau admin.
     *
     * @param {string} columnKey Identifiant de colonne
     * @param {string} width Largeur CSS
     */
    function applyColumnWidth(columnKey, width) {
      document.documentElement.style.setProperty(`--kl-col-${columnKey}`, width);

      document.querySelectorAll(`[data-kl-column="${columnKey}"]`).forEach((cell) => {
        cell.style.width = width;
        cell.style.minWidth = width;
        cell.style.maxWidth = width;
      });
    }

    /**
     * Restaure les largeurs sauvegardées dans le navigateur.
     */
    function restoreColumnWidths() {
      let widths = {};

      try {
        widths = JSON.parse(localStorage.getItem(storageKey) || '{}');
      } catch (error) {
        widths = {};
      }

      Object.entries(widths).forEach(([columnKey, width]) => {
        if (typeof width === 'string' && width !== '') {
          applyColumnWidth(columnKey, width);
        }
      });
    }

    /**
     * Active le redimensionnement par glisser-déposer sur les en-têtes.
     */
    function initResizableColumns() {
      document.querySelectorAll('.fi-ta-table').forEach((table) => {
        if (table.dataset.klResizeReady === '1') {
          return;
        }

        table.dataset.klResizeReady = '1';

        table.querySelectorAll('th[data-kl-column]').forEach((header) => {
          if (header.querySelector('.kl-col-resize-handle')) {
            return;
          }

          const columnKey = header.dataset.klColumn;
          const handle = document.createElement('div');
          handle.className = 'kl-col-resize-handle';
          handle.title = 'Glisser pour ajuster la largeur';
          header.appendChild(handle);

          handle.addEventListener('mousedown', (event) => {
            event.preventDefault();

            const startX = event.clientX;
            const startWidth = header.getBoundingClientRect().width;
            document.body.classList.add('kl-col-resizable-active');

            const onMouseMove = (moveEvent) => {
              const nextWidth = Math.max(160, Math.min(680, startWidth + (moveEvent.clientX - startX)));
              const widthValue = `${Math.round(nextWidth)}px`;
              applyColumnWidth(columnKey, widthValue);
            };

            const onMouseUp = () => {
              document.body.classList.remove('kl-col-resizable-active');
              document.removeEventListener('mousemove', onMouseMove);
              document.removeEventListener('mouseup', onMouseUp);

              let widths = {};

              try {
                widths = JSON.parse(localStorage.getItem(storageKey) || '{}');
              } catch (error) {
                widths = {};
              }

              widths[columnKey] = `${Math.round(header.getBoundingClientRect().width)}px`;
              localStorage.setItem(storageKey, JSON.stringify(widths));
            };

            document.addEventListener('mousemove', onMouseMove);
            document.addEventListener('mouseup', onMouseUp);
          });
        });
      });
    }

    /**
     * Initialise le comportement après chargement ou navigation Livewire.
     */
    function boot() {
      restoreColumnWidths();
      initResizableColumns();
    }

    document.addEventListener('DOMContentLoaded', boot);
    document.addEventListener('livewire:navigated', boot);
    document.addEventListener('livewire:initialized', boot);
  })();
</script>
