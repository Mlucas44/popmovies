<?php

namespace Drupal\tmdb_api\Service;

use GuzzleHttp\ClientInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Service pour interagir avec l'API TMDB.
 */
class TmdbClient
{
  /**
   * Le client HTTP de Drupal (Guzzle).
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Le service de cache.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cacheBackend;

  /**
   * Le logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Constructeur.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   */
  public function __construct(ClientInterface $http_client, LoggerChannelFactoryInterface $logger_factory, CacheBackendInterface $cache_backend)
  {
    $this->httpClient = $http_client;
    $this->logger = $logger_factory->get('tmdb_api');
    $this->cacheBackend = $cache_backend;
  }

  /**
   * Méthode générique centralisée pour faire une requête avec Cache natif.
   *
   * @param string $endpoint L'endpoint API (ex: 'movie/popular').
   * @param array $query_params Paramètres GET (ex: ['language' => 'fr-FR']).
   * @param int $cache_duration Durée du cache en secondes (Défaut: 3h).
   * @return array Résultat de l'API décodée.
   */
  private function requestApi(string $endpoint, array $query_params = [], int $cache_duration = 10800): array
  {
    // 1. Génération du Cache ID Unique en fonction des paramètres
    $cid = 'tmdb_api:' . md5($endpoint . serialize($query_params));

    // 2. Renvoi immédiat si présent dans le Cache
    if ($cache = $this->cacheBackend->get($cid)) {
      return $cache->data; // 0 appel réseau !
    }

    $token = Settings::get('tmdb_api_token');
    if (!$token) {
      $this->logger->error('Le token TMDB est manquant dans settings.php.');
      return [];
    }

    try {
      // 3. Appel de l'API
      $response = $this->httpClient->request('GET', "https://api.themoviedb.org/3/{$endpoint}", [
        'headers' => [
          'Authorization' => 'Bearer ' . $token,
          'accept' => 'application/json',
        ],
        'query' => $query_params,
      ]);

      $data = json_decode($response->getBody()->getContents(), TRUE) ?? [];

      // 4. Si la requête réseau réussie, on sauvegarde dans le Cache
      if (!empty($data) && $cache_duration > 0) {
        $this->cacheBackend->set($cid, $data, time() + $cache_duration);
      }

      return $data;
    } catch (\Exception $e) {
      $this->logger->error('Erreur API TMDB (@endpoint) : @msg', [
        '@endpoint' => $endpoint,
        '@msg' => $e->getMessage()
      ]);
      return [];
    }
  }

  /**
   * Méthode générique pour récupérer une liste de films.
   */
  private function fetchMovieList(string $endpoint, int $page = 1): array
  {
    $data = $this->requestApi("movie/{$endpoint}", [
      'language' => 'fr-FR',
      'page' => $page,
    ]);
    return $data['results'] ?? [];
  }

  /**
   * Récupère la liste des films populaires.
   */
  public function getPopularMovies(int $page = 1): array
  {
    return $this->fetchMovieList('popular', $page);
  }

  /**
   * Récupère la liste des films à l'affiche.
   */
  public function getNowPlayingMovies(int $page = 1): array
  {
    return $this->fetchMovieList('now_playing', $page);
  }

  /**
   * Récupère la liste des films à venir.
   */
  public function getUpcomingMovies(int $page = 1): array
  {
    $data = $this->requestApi('movie/upcoming', [
      'language' => 'fr-FR',
      'page' => $page,
    ]);
    return $data['results'] ?? [];
  }

  /**
   * Récupère la liste des films mieux notés.
   */
  public function getTopRatedMovies(int $page = 1): array
  {
    return $this->fetchMovieList('top_rated', $page);
  }

  /**
   * Récupère la liste des genres.
   */
  public function getGenres(string $language = 'fr-FR'): array
  {
    // Les genres ne changent jamais, on met en cache pendant 30 jours (2592000s)
    $data = $this->requestApi('genre/movie/list', ['language' => $language], 2592000);
    return $data['genres'] ?? [];
  }

  /**
   * Récupère les films selon l'ID d'un genre.
   */
  public function getMoviesByGenre($genre_id, int $page = 1, string $language = 'fr-FR'): array
  {
    $data = $this->requestApi('discover/movie', [
      'language' => $language,
      'page' => $page,
      'with_genres' => $genre_id,
      'sort_by' => 'popularity.desc',
    ]);
    return $data['results'] ?? [];
  }

  /**
   * Récupère les détails d'un film.
   */
  public function getMovieDetails(int $id, string $language = 'fr-FR'): array
  {
    // Les détails mis en cache 24h
    return $this->requestApi("movie/{$id}", ['language' => $language], 86400) ?: [];
  }

  /**
   * Récupère le casting.
   */
  public function getMovieCredits(int $id, string $language = 'fr-FR'): array
  {
    $data = $this->requestApi("movie/{$id}/credits", ['language' => $language], 86400);
    return $data['cast'] ?? [];
  }

  /**
   * Recherche de films (Live Search).
   */
  public function searchMovies(string $query, int $page = 1): array
  {
    // Les résultats de recherche mis en cache 1h ! 
    // Évite de spammer TMDB à chaque touche tapée par un même mot-clé
    $data = $this->requestApi('search/movie', [
      'language' => 'fr-FR',
      'query' => $query,
      'page' => $page,
      'include_adult' => 'false',
    ], 3600);
    return $data['results'] ?? [];
  }
}
