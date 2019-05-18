<?php

use Drupal\commerce_migrate_ubercart\Plugin\migrate\source\PaymentGateway as UbercartPaymentGateway;

@trigger_error('PaymentGateway is deprecated in Commerce Migrate 8.x-2.x-beta4 and will be removed before Commerce Migrate 8.x-3.x. Use \Drupal\commerce_migrate\modules\ubercart\source\PaymentGateway instead.', E_USER_DEPRECATED);

/**
 * Ubercart 6 payment gateway source.
 *
 * Migrate the Drupal 6 payment methods to a manual payment gateway.
 *
 * @MigrateSource(
 *   id = "uc6_payment_gateway",
 *   source_module = "uc_payment"
 * )
 * @deprecated in Commerce Migrate 8.x-2.x-beta4, to be removed before
 * Commerce Migrate 8.x-3.x. Use
 * \Drupal\commerce_migrate\modules\ubercart\source\PaymentGateway instead.
 */
class PaymentGateway extends UbercartPaymentGateway {}
