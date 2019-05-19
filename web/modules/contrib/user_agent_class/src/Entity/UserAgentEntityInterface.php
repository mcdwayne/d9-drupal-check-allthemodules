<?php

namespace Drupal\user_agent_class\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining User agent entity entities.
 */
interface UserAgentEntityInterface extends ConfigEntityInterface {

  /**
   * Returns the class name.
   *
   * @return $this
   *   The class name.
   */
  public function getClassName();

  /**
   * Sets the class name.
   *
   * @param string $class
   *   The class name.
   *
   * @return $this
   */
  public function setClassName($class);

}
