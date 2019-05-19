<?php

namespace Drupal\white_label_entity\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining While entity type entities.
 */
interface WhileEntityTypeInterface extends ConfigEntityInterface {

  /**
   * Gets the entity pages active state.
   *
   * @return bool
   *    Returns if entity pages are active for this entity type.
   */
  public function getEntityPagesActive();

}
