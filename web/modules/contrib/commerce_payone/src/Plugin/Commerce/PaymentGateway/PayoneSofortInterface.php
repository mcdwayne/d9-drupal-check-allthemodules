<?php

namespace Drupal\commerce_payone\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;

/**
 * Provides the interface for the SOFORT payment gateway.
 */
interface PayoneSofortInterface extends OffsitePaymentGatewayInterface {

  /**
   * Creates and initializes a Soforueberweisung API object.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment entity.
   * @param array $form
   *   The plugin form structure, must contain at least needed #return_url and
   *   #cancel_url keys.
   *
   *   The initialized Sofortueberweisung.
   */
  public function initializeSofortApi(PaymentInterface $payment, array $form);

}