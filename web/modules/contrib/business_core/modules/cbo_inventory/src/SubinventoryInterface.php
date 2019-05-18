<?php

namespace Drupal\cbo_inventory;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a subinventory entity.
 */
interface SubinventoryInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Gets the subinventory description.
   *
   * @return string
   *   Description of the subinventory.
   */
  public function getDescription();

}
