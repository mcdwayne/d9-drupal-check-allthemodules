<?php

namespace Drupal\commerce_paytabs\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the PayTabs payment gateway.
 */
interface PaytabsOffsiteRedirectInterface extends OffsitePaymentGatewayInterface, SupportsRefundsInterface {

  /**
   * Performs http request.
   *
   * @param string $api_uri
   *   The uri of the request.
   * @param array $paytabs_data
   *   The order entity, or null.
   *
   * @return array
   *   Paytabs response data.
   *
   */
  public function doHttpRequest($api_uri, array $paytabs_data);

}
