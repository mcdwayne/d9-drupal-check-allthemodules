<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\ShippingFlatRate as UbercartShippingFlatRate;

@trigger_error('ShippingFlatRate is deprecated in Commerce Migrate 8.x-2.x-beta4 and will be removed before Commerce Migrate 8.x-3.x. Use \Drupal\commerce_migrate\modules\ubercart\source\ShippingFlatRate instead.', E_USER_DEPRECATED);
/**
 * Gets the flat rate shipping service.
 *
 * @MigrateSource(
 *   id = "uc6_shipping_flat_rate",
 *   source_module = "uc_flatrate"
 * )
 *
 * @deprecated in Commerce Migrate 8.x-2.x-beta4, to be removed before
 * Commerce Migrate 8.x-3.x. Use
 * \Drupal\commerce_migrate\modules\ubercart\source\OrderProduct instead.
 */
class ShippingFlatRate extends UbercartShippingFlatRate {}
