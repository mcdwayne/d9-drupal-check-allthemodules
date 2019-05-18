<?php

namespace Drupal\dea;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityInterface;

/**
 * Interface for plugins providing additional logic to determine an entities
 * access relations.
 */
interface RequirementDiscoveryInterface {

  /**
   * Retrieve a list of related entities the user should also relate to to
   * execute a certain operation on this entity.
   *
   * @param EntityInterface $subject
   *   The entity that's about to be operated on.
   * @param EntityInterface $target
   *   The entity to be operated on.
   * @param string $operation
   *   The operation that is about to happen.
   *
   * @return EntityInterface[]
   *   List of entity id's required to execute this operation, keyed by entity
   *   type.
   */
  public function requirements(EntityInterface $subject, EntityInterface $target, $operation);

}