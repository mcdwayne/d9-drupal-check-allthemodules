<?php

namespace Drupal\entity_pilot\EntityResolver;

use Drupal\Core\Entity\EntityInterface;
use Drupal\serialization\EntityResolver\EntityResolverInterface;

/**
 * Defines an interface for unsaved UUID entity resolver.
 */
interface UnsavedUuidResolverInterface extends EntityResolverInterface {

  /**
   * Adds an entity to the stack of unresolved entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to add to the stack.
   *
   * @return self
   *   Instance method was called on.
   */
  public function add(EntityInterface $entity);

}
