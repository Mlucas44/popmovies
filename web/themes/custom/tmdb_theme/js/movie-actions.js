/**
 * @file
 * Gestion des actions utilisateur sur les films.
 */
(function (Drupal, once) {
  'use strict';

  Drupal.behaviors.tmdbMovieActions = {
    attach: function (context) {
      const buttons = once('tmdb-movie-actions', '.js-movie-action-btn', context);

      buttons.forEach(button => {
        button.addEventListener('click', function (e) {
          e.preventDefault();

          if (button.dataset.uid === '0') {
            window.location.href = '/user/login?destination=/profile';
            return;
          }

          const tmdbId = button.dataset.tmdbId;
          const actionType = button.dataset.actionType;
          const runtime = button.dataset.runtime;
          const svg = button.querySelector('svg');
          const isStrokeIcon = actionType === 'watched';

          button.classList.add('opacity-50', 'pointer-events-none');

          fetch('/api/movies/action/toggle', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
            },
            body: JSON.stringify({
              tmdb_id: tmdbId,
              action_type: actionType,
              runtime: runtime,
            }),
          })
            .then(response => {
              if (response.status === 403) {
                window.location.href = '/user/login?destination=/profile';
                throw new Error('Not logged in');
              }

              return response.json();
            })
            .then(data => {
              button.classList.remove('opacity-50', 'pointer-events-none');

              if (!svg || !data.status) {
                return;
              }

              if (data.status === 'added') {
                if (isStrokeIcon) {
                  svg.classList.add('fill-transparent', 'stroke-current', 'stroke-2');
                  svg.classList.remove('fill-current');
                } else {
                  svg.classList.add('fill-current');
                }

                button.classList.remove('bg-pop-nuit', 'text-pop-blanc');
                button.classList.add(
                  actionType === 'liked' ? 'bg-pop-rouge' : (actionType === 'watched' ? 'bg-blue-600' : 'bg-pop-or'),
                  actionType === 'watchlist' ? 'text-pop-nuit' : 'text-white'
                );

                button.classList.remove('hover:bg-pop-toile');
                return;
              }

              if (data.status === 'removed') {
                if (isStrokeIcon) {
                  svg.classList.add('fill-transparent', 'stroke-current', 'stroke-2');
                  svg.classList.remove('fill-current');
                } else {
                  svg.classList.add('fill-current');
                }

                button.classList.remove(
                  actionType === 'liked' ? 'bg-pop-rouge' : (actionType === 'watched' ? 'bg-blue-600' : 'bg-pop-or'),
                  actionType === 'watchlist' ? 'text-pop-nuit' : 'text-white'
                );

                button.classList.add('bg-pop-nuit', 'text-pop-blanc', 'hover:bg-pop-toile');
              }
            })
            .catch(error => {
              button.classList.remove('opacity-50', 'pointer-events-none');
              console.error('Action error:', error);
            });
        });
      });
    }
  };
})(Drupal, once);