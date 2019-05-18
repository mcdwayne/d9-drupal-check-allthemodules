<?php

namespace Drupal\flipping_book\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Flipping Book type entities.
 */
interface FlippingBookTypeInterface extends ConfigEntityInterface {

  /**
   * Returns the machine-readable permission name for the flipping book type.
   *
   * @return string|bool
   *   The machine-readable permission name, or FALSE if the flipping book type
   *   is malformed.
   */
  public function getPermissionName();

}
