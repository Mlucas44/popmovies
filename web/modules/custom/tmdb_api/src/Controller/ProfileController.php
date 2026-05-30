<?php

namespace Drupal\tmdb_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\tmdb_api\Service\TmdbClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Controller pour la page profil / dashboard utilisateur.
 */
class ProfileController extends ControllerBase
{

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * TMDB client.
   *
   * @var \Drupal\tmdb_api\Service\TmdbClient
   */
  protected $tmdbClient;

  /**
   * Renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Constructs a ProfileController object.
   */
  public function __construct(Connection $database, DateFormatterInterface $date_formatter, TmdbClient $tmdb_client, RendererInterface $renderer)
  {
    $this->database = $database;
    $this->dateFormatter = $date_formatter;
    $this->tmdbClient = $tmdb_client;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('database'),
      $container->get('date.formatter'),
      $container->get('tmdb_api.client'),
      $container->get('renderer')
    );
  }

  /**
   * Affiche le dashboard profil.
   */
  public function dashboard()
  {
    $uid = (int) $this->currentUser()->id();

    if ($uid === 0) {
      return [
        '#theme' => 'profile_dashboard_page',
        '#stats' => [],
        '#recent_actions' => [],
        '#access_denied' => TRUE,
      ];
    }

    $query = $this->database->select('tmdb_movie_actions', 't')
      ->fields('t', ['action_type', 'tmdb_id', 'runtime', 'created'])
      ->condition('uid', $uid)
      ->orderBy('created', 'DESC');
    $actions = $query->execute()->fetchAll(\PDO::FETCH_ASSOC);

    $stats = [
      'liked_count' => 0,
      'watched_count' => 0,
      'watchlist_count' => 0,
      'watched_runtime_minutes' => 0,
      'total_actions' => count($actions),
    ];

    $recent_actions = [];

    foreach ($actions as $action) {
      $action_type = $action['action_type'];
      if (isset($stats[$action_type . '_count'])) {
        $stats[$action_type . '_count']++;
      }

      if ($action_type === 'watched') {
        $stats['watched_runtime_minutes'] += (int) $action['runtime'];
      }

      if (count($recent_actions) < 6) {
        $movie_details = $this->tmdbClient->getMovieDetails((int) $action['tmdb_id']);

        $recent_actions[] = [
          'action_type' => $action_type,
          'tmdb_id' => (int) $action['tmdb_id'],
          'movie_title' => $movie_details['title'] ?? ('Film #' . (int) $action['tmdb_id']),
          'runtime' => (int) $action['runtime'],
          'created' => $this->dateFormatter->format((int) $action['created'], 'short'),
        ];
      }
    }

    return [
      '#theme' => 'profile_dashboard_page',
      '#stats' => $stats,
      '#recent_actions' => $recent_actions,
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];
  }

  /**
   * Liste initiale (page) pour un type d'action utilisateur : liked|watched|watchlist.
   */
  public function listPage($type)
  {
    $allowed = ['liked', 'watched', 'watchlist'];
    if (!in_array($type, $allowed)) {
      throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException();
    }

    $uid = (int) $this->currentUser()->id();
    if ($uid === 0) {
      return $this->redirect('user.login');
    }

    $limit = 20;
    $query = $this->database->select('tmdb_movie_actions', 't')
      ->fields('t', ['tmdb_id'])
      ->condition('uid', $uid)
      ->condition('action_type', $type)
      ->orderBy('created', 'DESC')
      ->range(0, $limit);
    $ids = $query->execute()->fetchCol();

    $movies = [];
    foreach ($ids as $tmdb_id) {
      $details = $this->tmdbClient->getMovieDetails((int) $tmdb_id);
      if (!empty($details)) {
        $movies[] = $details;
      }
    }

    $title_map = [
      'liked' => 'Liked',
      'watched' => 'Watched',
      'watchlist' => 'Watchlist',
    ];

    return [
      '#theme' => 'movie_page',
      '#title' => $title_map[$type] ?? ucfirst($type),
      '#intro_text' => '',
      '#movies' => $movies,
      '#api_url' => '/api/profile/' . $type,
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];
  }

  /**
   * API JSON pour l'infinite scroll des listes utilisateur.
   * Retourne un tableau d'objets { html: '<div>...</div>' }
   */
  public function listApi($type, $page = 1)
  {
    $allowed = ['liked', 'watched', 'watchlist'];
    if (!in_array($type, $allowed)) {
      return new JsonResponse([]);
    }

    $uid = (int) $this->currentUser()->id();
    if ($uid === 0) {
      return new JsonResponse([]);
    }

    $limit = 20;
    $offset = ((int) $page - 1) * $limit;

    $query = $this->database->select('tmdb_movie_actions', 't')
      ->fields('t', ['tmdb_id'])
      ->condition('uid', $uid)
      ->condition('action_type', $type)
      ->orderBy('created', 'DESC')
      ->range($offset, $limit);

    $ids = $query->execute()->fetchCol();

    $items = [];
    foreach ($ids as $tmdb_id) {
      $details = $this->tmdbClient->getMovieDetails((int) $tmdb_id);
      if (empty($details)) {
        continue;
      }

      $build = [
        '#theme' => 'movie_card',
        '#movie' => $details,
      ];

      $html = (string) $this->renderer->renderPlain($build);
      $items[] = ['html' => $html];
    }

    return new JsonResponse($items);
  }
}
