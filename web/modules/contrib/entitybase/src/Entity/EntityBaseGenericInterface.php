<?php

/**
 * @file
 * Contains \Drupal\entity_base\EntityBaseGenericInterface.
 */

namespace Drupal\entity_base\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

/**
 * Defines a common interface for all content entity objects.
 *
 * @see \Drupal\entity_base\EntityBaseGeneric
 *
 * @ingroup entity_api
 */
interface EntityBaseGenericInterface extends EntityBaseSimpleInterface {

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
   * @return \Drupal\entity_base\Entity\EntityBaseGenericInterface
   *   The called entity.
   */
  public function setType($type);

}
