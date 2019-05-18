<?php

namespace Drupal\commerce_smartpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;


/**
 * Provides the interface for the Smartpay payment gateway.
 */
interface SmartpayInterface extends OffsitePaymentGatewayInterface {

  /**
   * Gets the API URL.
   *
   * @return string
   *   The API URL.
   */
  public function getUrl();

  /**
   * Builds the transaction data.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *
   * @return array
   *  Transaction data.
   */
  public function buildTransaction(PaymentInterface $payment);

}