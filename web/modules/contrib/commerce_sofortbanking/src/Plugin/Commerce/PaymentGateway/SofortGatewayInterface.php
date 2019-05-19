<?php

namespace Drupal\commerce_sofortbanking\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface;

/**
 * Provides the interface for the SOFORT payment gateway.
 */
interface SofortGatewayInterface extends OffsitePaymentGatewayInterface {

  /**
   * Creates and initializes a Soforueberweisung API object.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment entity.
   * @param array $form
   *   The plugin form structure, must contain at least needed #return_url and
   *   #cancel_url keys.
   *
   * @return \Sofort\SofortLib\Sofortueberweisung
   *   The initialized Sofortueberweisung.
   */
  public function initializeSofortApi(PaymentInterface $payment, array $form);

}
