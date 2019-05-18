<?php

namespace Drupal\handy_cache_tags;

use Drupal\Core\Entity\EntityInterface;

/**
 * HandyCacheTagsManager service.
 */
class HandyCacheTagsManager {

  /**
   * The cache prefix we use for all cache tags.
   */
  const CACHE_PREFIX = 'handy_cache_tags';

  /**
   * Creates cache tags from entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity to use.
   *
   * @return array
   *   Array of cache tags.
   */
  public function getEntityTags(EntityInterface $entity) {
    return [
      $this->getEntityTypeTagFromEntity($entity),
      $this->getBundleTagFromEntity($entity),
    ];
  }

  /**
   * Gets entity type tags from the entity.
   */
  public function getEntityTypeTagFromEntity(EntityInterface $entity) {
    return $this->getTag($entity->getEntityTypeId());
  }

  /**
   * Gets a tag from a string.
   */
  public function getTag($type) {
    return sprintf('%s:%s', $this::CACHE_PREFIX, $type);
  }

  /**
   * Gets bundle tag for an entity.
   */
  public function getBundleTagFromEntity(EntityInterface $entity) {
    return $this->getBundleTag($entity->getEntityTypeId(), $entity->bundle());
  }

  /**
   * Gets a bundle tag from a type and a bundle.
   */
  public function getBundleTag($entity_type, $bundle) {
    return $this->getTag(sprintf('%s:%s', $entity_type, $bundle));
  }

}
