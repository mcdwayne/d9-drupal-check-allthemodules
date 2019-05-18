<?php

namespace Drupal\entity_pilot;

use Drupal\Core\Entity\EntityInterface;

/**
 * An interface for handling entity baggage (dependencies).
 */
interface BaggageHandlerInterface {

  /**
   * Calculate the dependencies for a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Entity to determine baggage for.
   * @param array $tags
   *   Array of cache tags.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|array
   *   Array of entities or stubs keyed by uuid.
   */
  public function calculateDependencies(EntityInterface $entity, array &$tags = []);

  /**
   * Resets the static cache.
   *
   * @return self
   *   The instance on which the method was called.
   */
  public function reset();

  /**
   * Generates field mapping for given entities.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface[] $entities
   *   Array of entities.
   *
   * @return array
   *   Array of field info keyed be entity type.
   */
  public function generateFieldMap(array $entities);

}
