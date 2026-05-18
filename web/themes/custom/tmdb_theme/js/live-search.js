(function (Drupal, once) {
  Drupal.behaviors.tmdbLiveSearch = {
    attach: function (context, settings) {
      // 1. Initialisation via @core/once
      const searchElements = once('tmdb-live-search', '#movie-search-input', context);
      if (searchElements.length === 0) return;
      const searchInput = searchElements[0];

      const resultsGrid = context.querySelector('#search-results-grid');
      const searchLoader = context.querySelector('#search-loader');
      const scrollTrigger = context.querySelector('#search-scroll-trigger');
      const searchResultsContainer = context.querySelector('#search-results-container');
      const homeDefaultContent = context.querySelector('#home-default-content');

      if (!resultsGrid) return;

      let timeoutId = null;
      let currentQuery = '';
      let currentPage = 1;
      let isLoading = false;
      let hasMore = true;

      // 1. Mise en place du Scroll Infini pour la recherche
      const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && !isLoading && hasMore && currentQuery.length >= 3) {
          loadMoreSearchResults(false);
        }
      }, { rootMargin: '250px' });

      observer.observe(scrollTrigger);

      // 2. Écoute de l'événement de frappe avec DEBOUNCE
      searchInput.addEventListener('input', (e) => {
        const query = e.target.value.trim();

        // On annule le timer précédent à chaque frappe
        clearTimeout(timeoutId);

        // Si moins de 3 caractères, on vide tout
        if (query.length < 3) {
          resultsGrid.innerHTML = '';
          scrollTrigger.classList.add('hidden');
          searchLoader.classList.add('hidden');
          if (searchResultsContainer) searchResultsContainer.classList.add('hidden');
          if (homeDefaultContent) homeDefaultContent.classList.remove('hidden');
          currentQuery = '';
          return;
        }

        // Sinon (Recherche en cours)
        if (homeDefaultContent) homeDefaultContent.classList.add('hidden');
        if (searchResultsContainer) searchResultsContainer.classList.remove('hidden');

        // On affiche un petit loader dans la barre de recherche
        searchLoader.classList.remove('hidden');

        // On crée un nouveau timer d'une seconde (1000 ms)
        timeoutId = setTimeout(() => {
          currentQuery = query;
          currentPage = 1;
          hasMore = true;
          resultsGrid.innerHTML = ''; // On vide la grille pour la nouvelle recherche
          loadMoreSearchResults(true);
        }, 1000);
      });

      // 3. Fonction pour appeler Drupal et afficher les cartes
      async function loadMoreSearchResults(isFirstPage = false) {
        isLoading = true;

        if (!isFirstPage) {
          currentPage++;
        }

        try {
          // Appel de la route API qu'on a créée dans le contrôleur
          const response = await fetch(`/api/movies/search?q=${encodeURIComponent(currentQuery)}&page=${currentPage}`);
          const movies = await response.json();

          searchLoader.classList.add('hidden'); // On cache le loader de la barre

          // Si aucun résultat
          if (movies.length === 0) {
            if (isFirstPage) {
              resultsGrid.innerHTML = '<p class="text-pop-blanc col-span-full text-center py-8 text-xl">Aucun résultat trouvé pour cette recherche.</p>';
            }
            scrollTrigger.classList.add('hidden');
            hasMore = false;
            isLoading = false;
            return;
          }

          scrollTrigger.classList.remove('hidden');

          // On utilise la propriété "html" renvoyée par le contrôleur Drupal (générée via Twig)
          const moviesHtml = movies.map(movie => movie.html).join('');

          // On ajoute les résultats à la suite
          resultsGrid.insertAdjacentHTML('beforeend', moviesHtml);
          isLoading = false;

        } catch (error) {
          console.error("Erreur de recherche API :", error);
          searchLoader.classList.add('hidden');
          isLoading = false;
        }
      }
    }
  };
})(Drupal, once);