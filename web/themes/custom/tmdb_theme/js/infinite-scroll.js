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
            trigger.innerHTML = '<p class="text-pop-blanc font-medium py-4">Fin de la liste</p>';
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