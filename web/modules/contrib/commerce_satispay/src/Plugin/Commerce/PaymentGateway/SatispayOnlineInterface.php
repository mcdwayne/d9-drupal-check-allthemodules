<?php

namespace Drupal\commerce_satispay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the Satispay payment gateway.
 */
interface SatispayOnlineInterface {

  /**
   * SetCheckout API Operation (NVP) request.
   *
   * Builds the data for the request and make the request.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param array $extra
   *   Extra data needed for this request.
   *
   * @return array
   *   Satispay response data.
   *
   * @see https://
   */
  public function setCheckout(PaymentInterface $payment, array $extra);

  /**
   * Check bearer.
   *
   * Verify credentials.
   *
   * @return bool
   *   Status.
   */
  public function check();

}
