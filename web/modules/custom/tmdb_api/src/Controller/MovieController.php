<?php

namespace Drupal\tmdb_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\tmdb_api\Service\TmdbClient;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * Contrôleur pour afficher les pages de films.
 */
class MovieController extends ControllerBase
{

  /**
   * Le service TMDB Client.
   *
   * @var \Drupal\tmdb_api\Service\TmdbClient
   */
  protected $tmdbClient;

  /**
   * Le service Renderer.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructeur.
   *
   * @param \Drupal\tmdb_api\Service\TmdbClient $tmdb_client
   * @param \Drupal\Core\Render\RendererInterface $renderer
   */
  public function __construct(TmdbClient $tmdb_client, RendererInterface $renderer)
  {
    $this->tmdbClient = $tmdb_client;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('tmdb_api.client'),
      $container->get('renderer')
    );
  }

  /**
   * Helper pour transformer une liste de films en réponse JSON avec l'HTML pré-rendu.
   */
  private function buildMoviesJsonResponse(array $movies): JsonResponse
  {
    $result = [];
    foreach ($movies as $movie) {
      $build = [
        '#theme' => 'movie_card',
        '#movie' => $movie,
      ];
      $result[] = [
        'html' => (string) $this->renderer->renderPlain($build),
      ];
    }
    return new JsonResponse($result);
  }

  /**
   * Route API pour le scroll infini (renvoie du JSON).
   */
  public function loadMoreGenres($genre_id, $page)
  {
    $movies = $this->tmdbClient->getMoviesByGenre($genre_id, $page);
    return $this->buildMoviesJsonResponse($movies);
  }
  /**
   * Route API pour le scroll infini des populaires (renvoie du JSON).
   */
  public function loadMorePopular($page)
  {
    $movies = $this->tmdbClient->getPopularMovies($page);
    return $this->buildMoviesJsonResponse($movies);
  }
  /**
   * Constructeur d'une page de films pour éviter la répétition.
   * 
   * @param string $title Le titre de la page.
   * @param string $intro_text Le texte introductif.
   * @param array $movies Les films à afficher.
   * @param string $api_url L'URL de l'API pour le scroll infini.
   * @return array La structure de rendu Drupal.
   */
  private function buildMoviePage(string $title, string $intro_text, array $movies, string $api_url): array
  {
    return [
      '#theme' => 'movie_page',
      '#title' => $title,
      '#intro_text' => $intro_text,
      '#movies' => $movies,
      '#api_url' => $api_url,
    ];
  }

  /**
   * Affiche la liste des films populaires.
   */
  public function popular()
  {
    $movies = $this->tmdbClient->getPopularMovies();
    return $this->buildMoviePage(
      'Films à l\'affiche',
      'Découvrez les films les plus populaires du moment selon la communauté TMDB.',
      $movies,
      '/api/movies/popular'
    );
  }

  /**
   * Affiche la liste des films pour un genre spécifique.
   *
   * @param int $genre_id
   * L'identifiant du genre passé dans l'URL.
   */
  public function genrePage($genre_id)
  {
    $movies = $this->tmdbClient->getMoviesByGenre($genre_id);

    $genres = $this->tmdbClient->getGenres('fr-FR');

    $genre_name = 'ce genre';

    foreach ($genres as $genre) {
      if ($genre['id'] == $genre_id) {
        $genre_name = $genre['name'];
        break;
      }
    }
    return [
      '#theme' => 'movie_page',
      '#title' => 'Films du genre : ' . $genre_name,
      '#intro_text' => 'Découvrez notre sélection de films pour la catégorie ' . $genre_name . '.',
      '#movies' => $movies,
      '#api_url' => '/api/genres/' . $genre_id,
    ];
  }
  /**
   * Affiche la page de détail d'un film.
   *
   * @param int $id L'identifiant du film passé dans l'URL.
   */
  public function detail($id)
  {
    $movie = $this->tmdbClient->getMovieDetails((int) $id);
    $movie['runtime_formatted'] = $this->tmdbClient->formatRuntime((int) ($movie['runtime'] ?? 0));

    $cast_full = $this->tmdbClient->getMovieCredits((int) $id);

    $cast = array_slice($cast_full, 0, 5);

    // Initialiser les actions vides.
    $user_actions = [];
    $uid = $this->currentUser()->id();
    if ($uid) {
      $query = \Drupal::database()->select('tmdb_movie_actions', 't')
        ->fields('t', ['action_type'])
        ->condition('uid', $uid)
        ->condition('tmdb_id', (int) $id)
        ->execute();
      $user_actions = $query->fetchCol(); // Retourne ['liked', 'watchlist', ...]
    }

    return [
      '#theme' => 'movie_detail_page',
      '#movie' => $movie,
      '#cast' => $cast,
      '#user_actions' => $user_actions,
      '#uid' => $uid,
      '#cache' => [
        'contexts' => ['user'],
        'tags' => ['movie_actions:' . $id . ':' . $uid],
      ],
    ];
  }

  /**
   * Affiche la liste des films à l'affiche.
   */
  public function nowPlaying()
  {
    $movies = $this->tmdbClient->getNowPlayingMovies();
    return $this->buildMoviePage(
      'Films à l\'affiche',
      'Découvrez les films actuellement à l\'affiche dans les salles.',
      $movies,
      '/api/movies/now-playing'
    );
  }

  /**
   * Route API pour le scroll infini des films à l'affiche (renvoie du JSON).
   */
  public function loadMoreNowPlaying($page)
  {
    $movies = $this->tmdbClient->getNowPlayingMovies($page);
    return $this->buildMoviesJsonResponse($movies);
  }

  /**
   * Affiche la liste des films à venir.
   */
  public function upcoming()
  {
    $movies = $this->tmdbClient->getUpcomingMovies();
    return $this->buildMoviePage(
      'Films à venir',
      'Découvrez les films qui arrivent bientôt au cinéma.',
      $movies,
      '/api/movies/upcoming'
    );
  }

  /**
   * Route API pour le scroll infini des films à venir (renvoie du JSON).
   */
  public function loadMoreUpcoming($page)
  {
    $movies = $this->tmdbClient->getUpcomingMovies($page);
    return $this->buildMoviesJsonResponse($movies);
  }

  /**
   * Affiche la liste des films mieux notés.
   */
  public function topRated()
  {
    $movies = $this->tmdbClient->getTopRatedMovies();
    return $this->buildMoviePage(
      'Mieux notés',
      'Découvrez les films ayant les meilleures notes de la communauté TMDB.',
      $movies,
      '/api/movies/top-rated'
    );
  }

  /**
   * Route API pour le scroll infini des films mieux notés (renvoie du JSON).
   */
  public function loadMoreTopRated($page)
  {
    $movies = $this->tmdbClient->getTopRatedMovies($page);
    return $this->buildMoviesJsonResponse($movies);
  }

  /**
   * Route API pour la recherche en temps réel (renvoie du JSON).
   */
  public function searchApi(Request $request)
  {
    // On récupère le mot-clé (?q=...) et la page (?page=...)
    $query = $request->query->get('q');
    $page = (int) $request->query->get('page', 1);

    // Sécurité : si la requête est vide ou fait moins de 3 caractères, on renvoie un tableau vide
    if (empty($query) || strlen($query) < 3) {
      return new JsonResponse([]);
    }

    $movies = $this->tmdbClient->searchMovies($query, $page);
    return $this->buildMoviesJsonResponse($movies);
  }
  /**
   * Affiche la page d'accueil avec la barre de recherche.
   */
  public function homePage()
  {
    $popular_movies = $this->tmdbClient->getPopularMovies(1);
    $now_playing_movies = $this->tmdbClient->getNowPlayingMovies(1);

    return [
      '#theme' => 'movie_home',
      '#popular_movies' => $popular_movies,
      '#now_playing_movies' => $now_playing_movies,
    ];
  }
}
