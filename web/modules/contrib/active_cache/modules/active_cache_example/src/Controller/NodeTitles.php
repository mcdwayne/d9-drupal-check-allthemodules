<?php

namespace Drupal\active_cache_example\Controller;
use Drupal\active_cache\Plugin\ActiveCacheInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Class NodeTitles
 * @package Drupal\active_cache_example\Controller
 */
class NodeTitles extends ControllerBase {

  /**
   * @var \Drupal\active_cache\Plugin\ActiveCacheInterface
   */
  protected $activeCache;

  /**
   * {@inheritdoc}
   */
  public function __construct(ActiveCacheInterface $active_cache) {
    $this->activeCache = $active_cache;
  }


  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.manager.active_cache')->getInstance(['id' => 'node_titles'])
    );
  }


  public function getNodeTitles() {
    $start = microtime(TRUE);
    $from_cache = $this->activeCache->isCached();
    $node_titles = $this->activeCache->getData();
    return new JsonResponse([
      'calculation_time' => microtime(TRUE) - $start,
      'from_cache' => $from_cache,
      'node_titles' => $node_titles,
    ]);
  }
}