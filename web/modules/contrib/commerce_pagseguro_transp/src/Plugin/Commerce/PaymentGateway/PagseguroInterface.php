<?php

namespace Drupal\commerce_pagseguro_transp\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsAuthorizationsInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;

/**
 * Provides the interface for the Payflow payment gateway.
 */
interface PagseguroInterface extends OnsitePaymentGatewayInterface, SupportsAuthorizationsInterface, SupportsRefundsInterface {

  /**
   * The access token.
   *
   * Used by the add-payment-method plugin form.
   *
   * @return string
   *   The client token.
   */
  public function getToken();

  /**
   * {@inheritdoc}
   */
  public function getEmail();

  /**
   * {@inheritdoc}
   */
  public function getEmailBuyer();

  /**
   * {@inheritdoc}
   */
  public function getNoInterestInstallmentQuantity();

}
