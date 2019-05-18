<?php

/**
 * @file
 * Contains \Drupal\blazemeter\BlazemeterUserInterface.
 */

namespace Drupal\blazemeter;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Blazemeter user entities.
 */
interface BlazemeterUserInterface extends ConfigEntityInterface {
  // Add get/set methods for your configuration properties here.
  public function username();
  public function password();

}
