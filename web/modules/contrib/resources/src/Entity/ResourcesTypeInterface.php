<?php

namespace Drupal\resources\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Resources type entities.
 */
interface ResourcesTypeInterface extends ConfigEntityInterface {

  // Add get/set methods for your configuration properties here.

  public function getDisplay();
}
