<?php

namespace Drupal\commerce_cib\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;

/**
 * Provides interface for the CIB payment gateway.
 */
interface CibInterface {

  /**
   * Create the CIB API request url based on the url query parameters.
   *
   * @param array $query
   *   An array of url query parameters.
   * @param string $market_customer
   *   Either 'market' or 'customer'.
   */
  public function createUrl(array $query, $market_customer = 'market');

  /**
   * Perform a simple request.
   *
   * Simple requests only have MSGT, TRID, AMO and PID parameters.
   *
   * @param PaymentInterface $payment
   *   The payment entity to use for the request.$this
   * @param int $msgt
   *   The request message type.
   *
   * @return array
   *   The response as an array.
   */
  public function simpleMsgt(PaymentInterface $payment, $msgt = 37);

  /**
   * Send a request to CIB.
   *
   * @param array $query
   *   The request parameters array.
   *
   * @return array
   *   The response parameters.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   *   Thrown when the request fails for any reason.
   */
  public function sendRequest(array $query);

}
