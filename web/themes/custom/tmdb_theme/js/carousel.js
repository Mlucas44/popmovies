/**
 * @file
 * Gestion des carrousels horizontaux.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.tmdbCarousel = {
    attach: function (context, settings) {
      // 1. Initialisation via once, le standard Drupal empêchant les exécutions multiples (ex: requêtes Ajax)
      const carousels = once('tmdb-carousel', '.js-carousel-wrapper', context);

      carousels.forEach(wrapper => {
        const container = wrapper.querySelector('.js-carousel-container');
        const btnPrev = wrapper.querySelector('.js-carousel-prev');
        const btnNext = wrapper.querySelector('.js-carousel-next');

        if (!container || !btnPrev || !btnNext) return;

        // Force le scroll en douceur
        container.style.scrollBehavior = 'smooth';

        // Fonction pour mettre à jour l'affichage des boutons
        const updateButtons = () => {
          if (container.scrollLeft > 2) {
            btnPrev.classList.remove('hidden');
          } else {
            btnPrev.classList.add('hidden');
          }

          if (container.scrollLeft + container.clientWidth >= container.scrollWidth - 2) {
            btnNext.classList.add('hidden');
          } else {
            btnNext.classList.remove('hidden');
          }
        };

        // Défiler vers la gauche
        btnPrev.addEventListener('click', () => {
          const scrollAmount = container.clientWidth * 0.8;
          container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
        });

        // Défiler vers la droite
        btnNext.addEventListener('click', () => {
          const scrollAmount = container.clientWidth * 0.8;
          container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
        });

        container.addEventListener('scroll', updateButtons);

        // Timeout léger pour s'assurer que le rendu CSS/DOM est terminé avant le calcul de la largeur
        setTimeout(updateButtons, 150);
        window.addEventListener('resize', updateButtons);
      });
    }
  };
})(Drupal, once);
