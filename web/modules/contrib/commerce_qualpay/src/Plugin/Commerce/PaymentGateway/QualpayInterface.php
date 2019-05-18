<?php

namespace Drupal\commerce_qualpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the Qualpay payment gateway.
 */
interface QualpayInterface extends OnsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {

  /**
   * Get the Qualpay API Publisable key set for the payment gateway.
   *
   * @return string
   *   The Qyalpay API publishable key.
   */
  //public function getShopId();

  public function getSecurity_key();

  public function getMerchant_id();

}
