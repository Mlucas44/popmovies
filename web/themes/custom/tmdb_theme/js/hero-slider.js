/**
 * @file
 * Gestion de l'animation du Hero Slider pour PopMovie.
 */

(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.heroSlider = {
    attach: function (context, settings) {
      // Utilisation du standard @core/once
      const processedTracks = once('hero-slider', '#hero-slider-track', context);
      if (processedTracks.length === 0) return;
      const track = processedTracks[0];

      const heroSlider = context.querySelector('#hero-slider');
      const prevBtn = context.querySelector('#slider-prev');
      const nextBtn = context.querySelector('#slider-next');
      const dots = context.querySelectorAll('.slider-dot');

      // On récupère le nombre de films depuis l'attribut HTML (car on n'a plus {{ hero_movies|length }} en js)
      const slideCount = parseInt(heroSlider.getAttribute('data-slide-count'), 10);
      if (isNaN(slideCount) || slideCount === 0) return;

      let currentIndex = 0;
      let autoPlayInterval;

      const updateSlider = () => {
        // Décaler le carrousel
        track.style.transform = `translateX(-${currentIndex * 100}%)`;

        // Mettre à jour les indicateurs
        dots.forEach((dot, index) => {
          if (index === currentIndex) {
            dot.classList.remove('bg-pop-blanc/50', 'w-2');
            dot.classList.add('bg-pop-blanc', 'w-8'); // Plus large pour le point actif
          } else {
            dot.classList.remove('bg-pop-blanc', 'w-8');
            dot.classList.add('bg-pop-blanc/50', 'w-2');
          }
        });
      };

      const nextSlide = () => {
        currentIndex = (currentIndex + 1) % slideCount;
        updateSlider();
      };

      const prevSlide = () => {
        currentIndex = (currentIndex - 1 + slideCount) % slideCount;
        updateSlider();
      };

      const resetAutoPlay = () => {
        clearInterval(autoPlayInterval);
        autoPlayInterval = setInterval(nextSlide, 6000); // 6 secondes de slide
      };

      // Pause au survol (UX amicale)
      if (heroSlider) {
        heroSlider.addEventListener('mouseenter', () => clearInterval(autoPlayInterval));
        heroSlider.addEventListener('mouseleave', resetAutoPlay);
      }

      // Écouteurs de clics
      if (nextBtn) nextBtn.addEventListener('click', () => { nextSlide(); resetAutoPlay(); });
      if (prevBtn) prevBtn.addEventListener('click', () => { prevSlide(); resetAutoPlay(); });

      dots.forEach(dot => {
        dot.addEventListener('click', (e) => {
          currentIndex = parseInt(e.target.dataset.index, 10);
          updateSlider();
          resetAutoPlay();
        });
      });

      // Lancement Initial
      updateSlider();
      resetAutoPlay();
    }
  };

})(Drupal, once);