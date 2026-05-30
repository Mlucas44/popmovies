// web/themes/custom/tmdb_theme/js/infinite-scroll.js

(function (Drupal) {
  Drupal.behaviors.tmdbInfiniteScroll = {
    attach: function (context, settings) {
      // On cherche les éléments dans le contexte de la page
      const grid = context.querySelector('#movies-grid');
      const trigger = context.querySelector('#scroll-trigger');

      // Sécurité : on vérifie que les éléments existent et qu'on ne l'a pas déjà lancé
      if (!grid || !trigger || grid.dataset.isObserving === 'true') {
        return;
      }

      // On marque la grille pour ne pas relancer l'observateur en boucle
      grid.dataset.isObserving = 'true';

      const genreId = grid.dataset.genreId;
      let currentPage = parseInt(grid.dataset.currentPage);
      let isLoading = false;

      const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && !isLoading) {
          loadMoreMovies();
        }
      }, { rootMargin: '250px' });

      observer.observe(trigger);

      async function loadMoreMovies() {
        isLoading = true;
        currentPage++;

        try {
          const response = await fetch(grid.dataset.apiUrl + `/${currentPage}`);
          const movies = await response.json();

          if (movies.length === 0) {
            trigger.innerHTML = `
              <div class="flex items-center gap-4 py-8 px-6">
                <span class="h-px flex-1 bg-gradient-to-r from-transparent via-pop-or/60 to-transparent"></span>
                <div class="flex flex-col items-center gap-3 text-center">
                  <div class="inline-flex items-center gap-2 rounded-full border border-pop-or/30 bg-pop-nuit/80 px-4 py-2 shadow-[0_0_30px_rgba(255,193,7,0.10)] backdrop-blur-sm">
                    <span class="w-2.5 h-2.5 rounded-full bg-pop-or animate-pulse"></span>
                    <span class="text-pop-or text-xs font-black uppercase tracking-[0.25em]">Fin de la liste</span>
                    <span class="w-2.5 h-2.5 rounded-full bg-pop-or animate-pulse"></span>
                  </div>
                  <p class="text-pop-mute text-sm max-w-md">Plus de films à afficher ici pour l’instant. Reviens plus tard ou explore une autre liste.</p>
                </div>
                <span class="h-px flex-1 bg-gradient-to-r from-transparent via-pop-or/60 to-transparent"></span>
              </div>
            `;
            observer.disconnect();
            return;
          }

          // On utilise la propriété "html" renvoyée par le contrôleur Drupal (générée via Twig)
          const moviesHtml = movies.map(movie => movie.html).join('');

          grid.insertAdjacentHTML('beforeend', moviesHtml);
          isLoading = false;

        } catch (error) {
          console.error("Erreur de chargement TMDB :", error);
          isLoading = false;
        }
      }
    }
  };
})(Drupal);