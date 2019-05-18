<?php

namespace Drupal\commerce_inventory\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\core_extend\Entity\EntityActiveInterface;
use Drupal\core_extend\Entity\EntityCreatedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining Inventory Location entities.
 *
 * @ingroup commerce_inventory
 */
interface InventoryLocationInterface extends ContentEntityInterface, EntityActiveInterface, EntityCreatedInterface, EntityOwnerInterface {

  /**
   * Whether item bundle fields are required for creation.
   *
   * @return bool
   *   True if the bundle field configuration is required. False otherwise.
   */
  public function isItemConfigurationRequired();

  /**
   * Sets the remote id for the relevant inventory provider.
   *
   * @param int|string $remote_id
   *   The remote id.
   * @param string $provider_id
   *   The provider plugin id.
   *
   * @return $this
   */
  public function setRemoteId($remote_id, $provider_id = NULL);

  /**
   * Get's the remote ID for the current inventory provider.
   *
   * @param string $provider_id
   *   The provider plugin id.
   *
   * @return int|string
   *   The remote ID.
   */
  public function getRemoteId($provider_id = NULL);

}
