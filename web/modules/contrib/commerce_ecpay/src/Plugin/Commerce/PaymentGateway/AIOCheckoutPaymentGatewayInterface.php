<?php

namespace Drupal\commerce_ecpay\Plugin\Commerce\PaymentGateway;


use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for ECPay AIO payment gateway
 *
 * @package Drupal\commerce_ecpay\Plugin\Commerce\PaymentGateway
 */
interface AIOCheckoutPaymentGatewayInterface extends SupportsAuthorizationsInterface, SupportsRefundsInterface {

  /**
   * Get the payment API URL.
   *
   * @param $uri
   * @return string The API URL
   * The API URL
   */
  public function getPaymentUrl($uri = '');

  /**
   * Get the vendor API URL.
   *
   * @param string $uri
   * @return string The API URL
   * The API URL
   */
  public function getVendorUrl($uri = '');

  /**
   * Performs a ECPay AIO payment checkout action
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *    The payment
   * @param array $extra
   *    Extra parameters needed for this request
   *
   * @return array
   *    Param values for building redirect form
   */
  public function checkout(PaymentInterface $payment, array $extra);
}
