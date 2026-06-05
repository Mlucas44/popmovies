<?php

namespace Drupal\tmdb_api\Cache\Context;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\Context\CacheContextInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Defines a Cache Context based on the TMDB region stored in the session.
 * 
 * Allows blocks and Dynamic Page Cache to maintain separate caches
 * for "FR", "US", "BR", etc., without polluting or blocking the user cache.
 */
class TmdbRegionCacheContext implements CacheContextInterface
{

  /**
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructor.
   */
  public function __construct(RequestStack $request_stack)
  {
    $this->requestStack = $request_stack;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel()
  {
    return t('TMDB Region');
  }

  /**
   * {@inheritdoc}
   */
  public function getContext()
  {
    $request = $this->requestStack->getCurrentRequest();
    if ($request && $request->hasSession()) {
      $session = $request->getSession();
      return $session->get('tmdb_api_region', 'FR');
    }
    return 'FR';
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheableMetadata()
  {
    return new CacheableMetadata();
  }
}
