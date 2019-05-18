<?php

namespace Drupal\commerce_migrate_ubercart\Plugin\migrate\destination\uc6;

use Drupal\commerce_migrate_ubercart\Plugin\migrate\destination\CommercePayment as CommonCommercePayment;

@trigger_error('CommercePayment is deprecated in Commerce Migrate 8.x-2.x-beta4 and will be removed before Commerce Migrate 8.x-3.x. Use \Drupal\commerce_migrate_ubercart\Plugin\migrate\destination\CommercePayment instead.', E_USER_DEPRECATED);

/**
 * Commerce payment destination for Ubercart 6.
 *
 * @deprecated in Commerce Migrate 8.x-2.x-beta4, to be removed before
 * Commerce Migrate 8.x-3.x. Use
 * \Drupal\commerce_migrate_ubercart\Plugin\migrate\destination\CommercePayment
 * instead.
 */
class CommercePayment extends CommonCommercePayment {}
