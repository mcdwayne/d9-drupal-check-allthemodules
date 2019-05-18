<?php

namespace Drupal\commerce_amazon_lpa\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\PaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsNotificationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Interface for Amazon Pay.
 */
interface AmazonPayInterface extends PaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface, SupportsNotificationsInterface {

  /**
   * Creates a payment.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the transaction fails for any reason.
   */
  public function createPayment(PaymentInterface $payment);

}
