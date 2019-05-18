<?php

namespace Drupal\uom;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface defining a UOM class entity type.
 */
interface UomClassInterface extends ConfigEntityInterface {

  /**
   * Returns the base UOM.
   *
   * @return string
   *   The base UOM.
   */
  public function getBaseUom();

}
