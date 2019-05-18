<?php

/**
 * @file
 * Contains \Drupal\packery\Entity\PackeryGroupInterface.
 */

namespace Drupal\packery\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface for Packery settings groups.
 */
interface PackeryGroupInterface extends ConfigEntityInterface {
  public function getSettings();
}
