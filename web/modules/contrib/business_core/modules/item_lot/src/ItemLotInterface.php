<?php

namespace Drupal\item_lot;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a resource entity.
 */
interface ItemLotInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Get the item.
   *
   * @return \Drupal\cbo_item\ItemInterface
   *   The item.
   */
  public function getItem();

}
