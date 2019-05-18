<?php

namespace Drupal\commerce_paytrail\Plugin\Commerce\PaymentType;

use Drupal\commerce_payment\Plugin\Commerce\PaymentType\PaymentDefault;

/**
 * Provides the default payment type.
 *
 * @CommercePaymentType(
 *   id = "paytrail",
 *   label = @Translation("Paytrail"),
 * )
 */
class Paytrail extends PaymentDefault {}
