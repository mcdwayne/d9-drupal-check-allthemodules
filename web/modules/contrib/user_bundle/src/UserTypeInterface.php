<?php

namespace Drupal\user_bundle;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a user type entity.
 */
interface UserTypeInterface extends ConfigEntityInterface {

  /**
   * Determines whether the user type is locked.
   *
   * @return string|false
   *   The module name that locks the type or FALSE.
   */
  public function isLocked();

  /**
   * Gets the description.
   *
   * @return string
   *   The description of this user type.
   */
  public function getDescription();

}
