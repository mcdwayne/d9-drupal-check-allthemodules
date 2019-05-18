<?php

namespace Drupal\commerce_inventory\Plugin\Commerce\InventoryProvider;

/**
 * Provides a default provider using Drupal storage.
 *
 * @CommerceInventoryProvider(
 *   id = "default",
 *   label = @Translation("Default"),
 *   description = @Translation("Local inventory management."),
 *   category = @Translation("Local")
 * )
 */
class DefaultProvider extends InventoryProviderBase implements InventoryProviderInterface {

  // Nothing to see here.
}
