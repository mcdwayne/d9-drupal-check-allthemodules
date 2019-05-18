<?php

namespace Drupal\commerce_gocardless\Plugin\Commerce\PaymentMethodType;

use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentMethodType\PaymentMethodTypeBase;

/**
 * Provides the payment method type for GoCardless one-off payments.
 *
 * Payment methods of this type should use getRemoteId/setRemoteID for the
 * GoCardless mandate ID.
 *
 * @CommercePaymentMethodType(
 *   id = "commerce_gocardless_oneoff",
 *   label = @Translation("GoCardless one-off payment"),
 *   create_label = @Translation("New direct debit"),
 * )
 */
class OneOff extends PaymentMethodTypeBase {

  /**
   * {@inheritdoc}
   */
  public function buildLabel(PaymentMethodInterface $payment_method) {
    if ($payment_method->getRemoteId()) {
      /** @var \Drupal\commerce_gocardless\Plugin\Commerce\PaymentGateway\GoCardlessPaymentGatewayInterface $payment_gateway */
      $payment_gateway = $payment_method->getPaymentGateway()->getPlugin();
      return $this->t('Existing direct debit: @description', [
        '@description' => $payment_gateway->getMandateDescription($payment_method),
      ]);
    }
    else {
      return $this->t('Existing direct debit');
    }
  }

}
