<?php

namespace Drupal\commerce_clic\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;

/**
 * Provides the interface for the Rave payment gateway.
 */
interface ClicInterface extends OffsitePaymentGatewayInterface {

  const CLIC_AUTH_HEADER = 'Clic-Authorization';

  /**
   * Get the configured Rave API Secret key.
   *
   * @return string
   *   The Rave API Secret key.
   */
  public function getSecretKey();

  /**
   * Get the configured Rave API Public key.
   *
   * @return string
   *   The Rave API Public key.
   */
  public function getPublicKey();

}
