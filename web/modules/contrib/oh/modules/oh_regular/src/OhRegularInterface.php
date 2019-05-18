<?php

namespace Drupal\oh_regular;

use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for OH regular service.
 */
interface OhRegularInterface {

  /**
   * Determines whether an entity uses opening hours.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   An entity.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   Whether the entity uses opening hours.
   */
  public function hasOpeningHours(EntityInterface $entity): AccessResultInterface;

  /**
   * Get field mapping for all bundles.
   *
   * @return array
   *   Fields keyed by entity type and bundle.
   */
  public function getAllMapping(): array;

  /**
   * Get field mapping for a bundle.
   *
   * @param string $entityTypeId
   *   The entity type Id.
   * @param string $bundle
   *   The bundle.
   *
   * @return array
   *   List of field names.
   */
  public function getMapping(string $entityTypeId, string $bundle): array;

}
