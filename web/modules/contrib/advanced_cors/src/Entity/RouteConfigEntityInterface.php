<?php

namespace Drupal\advanced_cors\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Route configuration entities.
 */
interface RouteConfigEntityInterface extends ConfigEntityInterface {

  /**
   * Path patterns.
   */
  public function getPatterns();

}
