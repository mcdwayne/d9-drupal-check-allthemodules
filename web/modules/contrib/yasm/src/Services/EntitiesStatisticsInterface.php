<?php

namespace Drupal\yasm\Services;

use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines entities statistics interface.
 */
interface EntitiesStatisticsInterface {

  /**
   * Count entities.
   *
   * @param string $entity_id
   *   The entity id.
   * @param array $conditions
   *   Filter conditions with key => value. Empty array to not filter.
   *
   * @return int
   *   The entity count.
   */
  public function count($entity_id, array $conditions = []);

  /**
   * Count entities with aggregation.
   */
  public function aggregate($entity_id, array $aggregates = [], $group_by = NULL, array $conditions = []);

  /**
   * Get site content entities.
   */
  public function getEntitiesInfo(array $conditions = []);

  /**
   * Get entity and parent bundles info.
   */
  public function getEntityAndBundlesInfo(EntityTypeInterface $entity, array $conditions = []);

  /**
   * Get entity basic data.
   */
  public function getEntityInfo(EntityTypeInterface $entity, array $conditions = []);

  /**
   * Get bundle basic data.
   */
  public function getBundleInfo(EntityTypeInterface $entity, $bundle, array $conditions = []);

  /**
   * Count entity elements.
   */
  public function countEntityElements(EntityTypeInterface $entity, array $conditions = []);

  /**
   * Count bundle elements.
   */
  public function countBundleElements(EntityTypeInterface $entity, $bundle, array $conditions = []);

  /**
   * Get first date creation content for entity type.
   */
  public function getFirstDateContent($entity_id);

}
