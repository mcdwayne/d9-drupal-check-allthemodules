<?php

namespace Drupal\entity_generic\Entity;

/**
 * Defines an interface for label.
 */
interface EntityTypedInterface {

  /**
   * Returns the entity type.
   */
  public function getType();


  /**
   * Sets the entity type.
   *
   * @param string $type
   *   The entity type.
   *
   * @return \Drupal\entity_generic\Entity\GenericInterface
   *   The called entity.
   */
  public function setType($type);

}
