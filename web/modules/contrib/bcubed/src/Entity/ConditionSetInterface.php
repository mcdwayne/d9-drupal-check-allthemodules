<?php

namespace Drupal\bcubed\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Condition Set entities.
 */
interface ConditionSetInterface extends ConfigEntityInterface {

  /**
   * Return the status of the conditionset.
   *
   * @return bool
   *   status of the conditionset
   */
  public function status();

  /**
   * Returns a list of the JS plugin libraries loaded by the conditionset.
   *
   * @return array
   *   list of JS libraries
   */
  public function getJsPlugins();

}
