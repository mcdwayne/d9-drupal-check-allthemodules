<?php

/**
 * @file
 * Contains Drupal\environment\EnvironmentInterface.
 */

namespace Drupal\environment;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Environment entities.
 */
interface EnvironmentInterface extends ConfigEntityInterface {

  public function getDescription();

}
