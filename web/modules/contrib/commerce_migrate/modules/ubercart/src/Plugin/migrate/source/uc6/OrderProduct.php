<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\OrderProduct as UbercartOrderProduct;

@trigger_error('OrderProduct is deprecated in Commerce Migrate 8.x-2.x-beta4 and will be removed before Commerce Migrate 8.x-3.x. Use \Drupal\commerce_migrate\modules\ubercart\source\OrderProduct instead.', E_USER_DEPRECATED);

/**
 * Ubercart 6 order product source.
 *
 * @MigrateSource(
 *   id = "uc6_order_product",
 *   source_module = "uc_order",
 * )
 *
 * @deprecated in Commerce Migrate 8.x-2.x-beta4, to be removed before
 * Commerce Migrate 8.x-3.x. Use
 * \Drupal\commerce_migrate\modules\ubercart\source\OrderProduct instead.
 */
class OrderProduct extends UbercartOrderProduct {}
