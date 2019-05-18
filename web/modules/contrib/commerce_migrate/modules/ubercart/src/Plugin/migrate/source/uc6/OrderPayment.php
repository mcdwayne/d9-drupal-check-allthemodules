<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc6;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\OrderPayment as UbercartOrderPayment;

@trigger_error('OrderPayment is deprecated in Commerce Migrate 8.x-2.x-beta4 and will be removed before Commerce Migrate 8.x-3.x. Use \Drupal\commerce_migrate\modules\ubercart\source\OrderPayment instead.', E_USER_DEPRECATED);

/**
 * Provides migration source for orders.
 *
 * @MigrateSource(
 *   id = "uc6_payment_receipt",
 *   source_module = "uc_payment"
 * )
 *
 * @deprecated in Commerce Migrate 8.x-2.x-beta4, to be removed before
 * Commerce Migrate 8.x-3.x. Use
 * \Drupal\commerce_migrate\modules\ubercart\source\OrderProduct instead.
 */
class OrderPayment extends UbercartOrderPayment {}
