<?php

namespace Drupal\cbo_inventory;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Provides an interface defining a subinventory type entity.
 */
interface SubinventoryTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface {

  /**
   * Determines whether the subinventory type is locked.
   *
   * @return string|false
   *   The module name that locks the type or FALSE.
   */
  public function isLocked();

}
