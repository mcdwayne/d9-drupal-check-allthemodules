<?php

namespace Drupal\commerce_addon\Entity;

use Drupal\commerce\PurchasableEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Interface ServiceLevelAddonInterface.
 */
interface AddonInterface extends PurchasableEntityInterface, EntityChangedInterface, EntityPublishedInterface {

  /**
   * @return mixed
   */
  public function getDescription();

}
