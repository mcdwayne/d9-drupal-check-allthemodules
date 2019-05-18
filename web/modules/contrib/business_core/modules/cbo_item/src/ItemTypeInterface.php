<?php

namespace Drupal\cbo_item;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Provides an interface defining a device type entity.
 */
interface ItemTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface {

  /**
   * Determines whether the item type is locked.
   *
   * @return string|false
   *   The module name that locks the type or FALSE.
   */
  public function isLocked();

}
