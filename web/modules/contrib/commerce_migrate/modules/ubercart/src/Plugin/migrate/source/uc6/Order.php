<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\Order as UbercartOrder;

@trigger_error('Order is deprecated in Commerce Migrate 8.x-2.x-beta4 and will be removed before Commerce Migrate 8.x-3.x. Use \Drupal\commerce_migrate\modules\ubercart\source\Order instead.', E_USER_DEPRECATED);

/**
 * Provides migration source for orders.
 *
 * @MigrateSource(
 *   id = "uc6_order",
 *   source_module = "uc_order"
 * )
 *
 * @deprecated in Commerce Migrate 8.x-2.x-beta4, to be removed before
 * Commerce Migrate 8.x-3.x. Use
 * \Drupal\commerce_migrate\modules\ubercart\source\Order instead.
 */
class Order extends UbercartOrder {}
