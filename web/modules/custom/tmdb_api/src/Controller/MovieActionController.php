<?php

namespace Drupal\tmdb_api\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Database\Connection;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Cache\Cache;

/**
 * Controller pour gérer les actions des utilisateurs (Liked, Watched, Watchlist).
 */
class MovieActionController extends ControllerBase
{

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a MovieActionController object.
   *
   * @param \Drupal\Core\Database\Connection $database
   */
  public function __construct(Connection $database)
  {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('database')
    );
  }

  /**
   * Toggle action state.
   */
  public function toggle(Request $request)
  {
    $user = $this->currentUser();
    if ($user->isAnonymous()) {
      return new JsonResponse(['error' => 'user_unauthenticated'], 403);
    }

    $content = $request->getContent();
    $data = json_decode($content, TRUE);
    $tmdb_id = isset($data['tmdb_id']) ? (int) $data['tmdb_id'] : 0;
    $action_type = isset($data['action_type']) ? $data['action_type'] : '';
    $runtime = isset($data['runtime']) ? (int) $data['runtime'] : 0;

    if (!$tmdb_id || !in_array($action_type, ['liked', 'watched', 'watchlist'])) {
      return new JsonResponse(['error' => 'invalid_input'], 400);
    }

    $uid = $user->id();

    // Vérifier si cette action existe déjà pour ce film et cet utilisateur.
    $existing_id = $this->database->select('tmdb_movie_actions', 't')
      ->fields('t', ['id'])
      ->condition('uid', $uid)
      ->condition('tmdb_id', $tmdb_id)
      ->condition('action_type', $action_type)
      ->execute()
      ->fetchField();

    if ($existing_id) {
      // Si on l'a déjà liké on supprime (= Toggle Off)
      $this->database->delete('tmdb_movie_actions')
        ->condition('id', $existing_id)
        ->execute();
      $status = 'removed';
    } else {
      // Sinon on l'ajoute (= Toggle On)
      $this->database->insert('tmdb_movie_actions')
        ->fields([
          'uid' => $uid,
          'tmdb_id' => $tmdb_id,
          'action_type' => $action_type,
          'runtime' => $runtime,
          'created' => \Drupal::time()->getRequestTime(),
        ])
        ->execute();
      $status = 'added';
    }

    // Invalider le cache de la page de ce film pour cet utilisateur spécifique.
    Cache::invalidateTags(['movie_actions:' . $tmdb_id . ':' . $uid]);

    return new JsonResponse([
      'status' => $status,
      'action_type' => $action_type,
      'tmdb_id' => $tmdb_id,
    ]);
  }
}
