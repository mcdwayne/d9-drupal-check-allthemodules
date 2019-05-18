<?php

namespace Drupal\advanced_cors;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityManagerInterface;

/**
 * Class PatternsCache.
 */
class PatternsCache {

  /**
   * The entity storage class.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * The cache backend that should be used.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Constructs a Cache using a string storage.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entityManager
   *   Storage to use when looking for new translations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   The cache backend.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   */
  public function __construct(EntityManagerInterface $entityManager, CacheBackendInterface $cache) {
    $this->storage = $entityManager->getStorage('route_config');
    $this->cache = $cache;
  }

  /**
   * Set cache.
   */
  protected function setCache(array $data) {
    $this->cache->set('advanced_cors:patterns_cache', $data);
  }

  /**
   * Get cache.
   */
  protected function getCache() {
    return $this->cache->get('advanced_cors:patterns_cache');
  }

  /**
   * Reset cache.
   */
  public function resetCache() {
    return $this->cache->delete('advanced_cors:patterns_cache');
  }

  /**
   * Get patterns list.
   */
  public function getPatterns() {
    $cache = $this->getCache();

    if (!empty($cache->valid)) {
      return $cache->data;
    }

    /** @var \Drupal\advanced_cors\Entity\RouteConfigEntityInterface[] $entities */
    $entities = $this->storage->loadByProperties(['status' => 1]);
    $result = [];

    uasort($entities, [$this->storage->getEntityType()->getClass(), 'sort']);

    foreach ($entities as $entity) {
      $result += array_fill_keys($entity->getPatterns(), $entity->id());
    }

    $this->setCache($result);

    return $result;
  }

}
