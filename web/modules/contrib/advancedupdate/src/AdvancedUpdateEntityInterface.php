<?php

namespace Drupal\advanced_update;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Advanced update entity entities.
 */
interface AdvancedUpdateEntityInterface extends ConfigEntityInterface {

  /**
   * Get the creation date of updates.
   *
   * @return string
   *    Return a timestamp of the date.
   */
  public function date();

  /**
   * Get the class name of updates.
   *
   * @return string
   *    Return the class name.
   */
  public function className();

  /**
   * Get the module name of updates.
   *
   * @return string
   *    Return the module name.
   */
  public function moduleName();

}
