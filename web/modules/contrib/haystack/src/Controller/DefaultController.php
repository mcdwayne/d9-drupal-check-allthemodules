<?php

namespace Drupal\haystack\Controller;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Default controller for the haystack module.
 */
class DefaultController extends ControllerBase {

  /**
   * The cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * DefaultController constructor.
   *
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache service with the 'default' bin loaded.
   */
  public function __construct(CacheBackendInterface $cache) {
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $cache = $container->get('cache.default');

    return new static($cache);
  }

  /**
   * Callback for analytics AJAX request.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   Returns a JSON object.
   */
  public function haystackAjax() {
    // Get cache analytics data.
    $cur_cache = $this->cache->get('haystack_analytics');
    if ($cur_cache) {
      $cur_cache = $cur_cache->data;
    }
    else {
      $cur_cache = [];
    }

    array_push($cur_cache, $_GET);
    $this->cache->set('haystack_analytics', $cur_cache);

    return new JsonResponse([
      'analytics_check' => TRUE,
      'cur_cache' => TRUE,
    ]);
  }

}
