<?php

namespace Drupal\oh_regular;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\oh_regular\Entity\OhRegularMap;

/**
 * OH regular service.
 */
class OhRegular implements OhRegularInterface {

  /**
   * Cache ID for the entire regular mapping cache.
   *
   * Cache is global and does not vary.
   */
  const REGULAR_MAPPING_CID = 'oh:field_mapping:regular';

  /**
   * Regular map storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $regularMapStorage;

  /**
   * Cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Construct OhRegularSubscriber service.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache backend.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, CacheBackendInterface $cache) {
    $this->regularMapStorage = $entityTypeManager->getStorage('oh_regular_map');
    $this->cache = $cache;
  }

  /**
   * {@inheritdoc}
   */
  public function hasOpeningHours(EntityInterface $entity): AccessResultInterface {
    $mapping = $this->getMapping($entity->getEntityTypeId(), $entity->bundle());
    $access = (AccessResult::allowedIf(!empty($mapping)))
      ->addCacheTags([OhRegularMap::CACHE_TAG_ALL]);
    return $access;
  }

  /**
   * {@inheritdoc}
   */
  public function getAllMapping(): array {
    $mapping = $this->cache->get(static::REGULAR_MAPPING_CID);
    if (FALSE === $mapping) {
      /** @var \Drupal\oh_regular\OhRegularMapInterface[] $maps */
      $maps = $this->regularMapStorage->loadMultiple();
      $mapping = [];
      foreach ($maps as $map) {
        $mapping[$map->getMapEntityType()][$map->getMapBundle()] = array_map(
          function (array $fieldMap) {
            return $fieldMap['field_name'];
          },
          $map->getRegularFields()
        );
      }

      $this->cache
        ->set(static::REGULAR_MAPPING_CID, $mapping, Cache::PERMANENT, [OhRegularMap::CACHE_TAG_ALL]);
    }
    else {
      $mapping = $mapping->data;
    }

    return $mapping;
  }

  /**
   * {@inheritdoc}
   */
  public function getMapping(string $entityTypeId, string $bundle): array {
    $mapping = $this->getAllMapping();
    return $mapping[$entityTypeId][$bundle] ?? [];
  }

}
