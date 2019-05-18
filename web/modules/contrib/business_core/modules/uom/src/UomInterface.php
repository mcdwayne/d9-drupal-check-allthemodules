<?php

namespace Drupal\uom;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a UOM entity type.
 */
interface UomInterface extends ConfigEntityInterface {

  /**
   * Returns the UOM class.
   *
   * @return string
   *   The UOM class.
   */
  public function getClass();

}
