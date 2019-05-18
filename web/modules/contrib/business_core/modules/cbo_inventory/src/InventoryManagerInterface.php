<?php

namespace Drupal\cbo_inventory;

/**
 * Inventory manager contains common functions to manage inventory.
 */
interface InventoryManagerInterface {

  /**
   * Gets the current active user's inventory organization.
   *
   * @return \Drupal\cbo_organization\OrganizationInterface
   *   The inventory organization for current active user.
   */
  public function currentInventoryOrganization();

}
