<?php

namespace Drupal\commerce_payway\Client;

use Drupal\commerce_payment\Entity\PaymentInterface;

/**
 * Pay Way Client interface.
 */
interface PayWayRestApiClientInterface {

  /**
   * Execute the payment request to payway.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment.
   * @param array $configuration
   *   The payment method configuration.
   *
   * @throws \Drupal\commerce_payway\Exception\PayWayClientException
   */
  public function doRequest(PaymentInterface $payment, array $configuration);

  /**
   * Get client response.
   *
   * @return string
   *   Body of the client response.
   */
  public function getResponse();

}
