<?php

namespace Drupal\dat\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for defining Database connection entities.
 */
interface DatabaseConnectionInterface extends ConfigEntityInterface {

  /**
   * Gets the Database Connection host with port if defined.
   *
   * @return string
   *   The Database Connection host with port.
   */
  public function getHostWithPort(): string;

}
