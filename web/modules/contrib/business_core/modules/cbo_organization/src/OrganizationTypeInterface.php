<?php

namespace Drupal\cbo_organization;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\EntityDescriptionInterface;

/**
 * Provides an interface defining a device type entity.
 */
interface OrganizationTypeInterface extends ConfigEntityInterface, EntityDescriptionInterface {

  /**
   * Determines whether the organization type is locked.
   *
   * @return string|false
   *   The module name that locks the type or FALSE.
   */
  public function isLocked();

}
