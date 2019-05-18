<?php

namespace Drupal\entity_generic\Entity;

/**
 * Defines an interface for label.
 */
interface EntityLabelInterface {

  /**
   * Gets the entity label.
   *
   * @return string
   *   Label of the entity.
   */
  public function getLabel();

  /**
   * Sets the entity label.
   *
   * @param string $label
   *   The entity label.
   *
   * @return \Drupal\entity_generic\Entity\SimpleInterface
   *   The called entity.
   */
  public function setLabel($label);

}
