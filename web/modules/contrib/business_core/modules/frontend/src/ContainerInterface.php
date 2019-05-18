<?php

namespace Drupal\frontend;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface definition a container entity.
 */
interface ContainerInterface extends ConfigEntityInterface {

  /**
   * Determines whether the container is locked.
   *
   * @return string|false
   *   The module name that locks the container or FALSE.
   */
  public function isLocked();

}
