<?php

namespace Drupal\active_cache_test\Controller;

use Drupal\active_cache\Plugin\ActiveCacheManager;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class ActiveCacheTest
 * @package Drupal\active_cache_test\Controller
 */
class ActiveCacheTest extends ControllerBase {

  /**
   * @var \Drupal\active_cache\Plugin\ActiveCacheManager
   */
  protected $activeCacheManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(ActiveCacheManager $activeCacheManager) {
    $this->activeCacheManager = $activeCacheManager;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.active_cache')
    );
  }


  public function getContents() {
    $active_cache = $this->activeCacheManager->getInstance(['id' => 'simple_database']);

    return new JsonResponse($active_cache->getData());
  }
}