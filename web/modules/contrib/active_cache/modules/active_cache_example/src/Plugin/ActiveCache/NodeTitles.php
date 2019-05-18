<?php

namespace Drupal\active_cache_example\Plugin\ActiveCache;

use Drupal\active_cache\Plugin\ActiveCacheBase;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\node\NodeStorageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @ActiveCache(
 *  id = "node_titles",
 *  label = @Translation("Node titles"),
 * )
 */
class NodeTitles extends ActiveCacheBase {

  /**
   * @var \Drupal\node\NodeStorageInterface
   */
  protected $nodeStorage;

  /**
   * {@inheritdoc}
   */
  public function __construct(NodeStorageInterface $node_storage, CacheBackendInterface $cache_backend, CacheBackendInterface $static_cache, array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($cache_backend, $static_cache, $configuration, $plugin_id, $plugin_definition);
    $this->nodeStorage = $node_storage;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $cache_bin = $plugin_definition['cache_bin'];
    return new static(
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get("cache.{$cache_bin}"),
      $container->get('cache.static'),
      $configuration, $plugin_id, $plugin_definition
    );
  }

  /**
   * @return string[]
   */
  public function getCacheTags() {
    $tags = $this->nodeStorage->getEntityType()->getListCacheTags();

    foreach ($this->loadNodes() as $node) {
      $tags = Cache::mergeTags($tags, $node->getCacheTags());
    }

    return $tags;
  }


  /**
   * Builds the data that will be cached.
   *
   * @return mixed
   */
  protected function buildData() {
    $data = [];

    foreach ($this->loadNodes() as $node) {
      $data[$node->id()] = $node->label();
    }

    return $data;
  }

  /**
   * @return \Drupal\node\NodeInterface[]
   */
  protected function loadNodes() {
    return $this->nodeStorage->loadByProperties(['type' => 'active_cache_example']);
  }
}
