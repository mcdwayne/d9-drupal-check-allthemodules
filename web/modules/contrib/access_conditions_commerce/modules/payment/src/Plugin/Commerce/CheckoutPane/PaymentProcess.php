<?php

namespace Drupal\access_conditions_commerce_payment\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentProcess as PaymentProcessBase;

/**
 * Provides the payment process pane with access conditions visibility.
 *
 * @CommerceCheckoutPane(
 *   id = "access_conditions_payment_process",
 *   label = @Translation("Payment process with access conditions"),
 *   default_step = "_disabled",
 *   wrapper_element = "container",
 * )
 */
class PaymentProcess extends PaymentProcessBase {

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    if ($this->order->isPaid() || $this->order->getTotalPrice()->isZero()) {
      // No payment is needed if the order is free or has already been paid.
      return FALSE;
    }
    $payment_info_pane = $this->checkoutFlow->getPane('access_conditions_payment_information');
    if (!$payment_info_pane->isVisible() || $payment_info_pane->getStepId() == '_disabled') {
      // Hide the pane if the PaymentInformation pane has been disabled.
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Gets the step ID that the customer should be sent to on error.
   *
   * @return string
   *   The error step ID.
   */
  protected function getErrorStepId() {
    // Default to the step that contains the PaymentInformation pane.
    $step_id = $this->checkoutFlow->getPane('access_conditions_payment_information')->getStepId();
    if ($step_id == '_disabled') {
      // Can't redirect to the _disabled step. This could mean that isVisible()
      // was overridden to allow PaymentProcess to be used without a
      // access_conditions_payment_information pane, but this method was not
      // modified.
      throw new \RuntimeException('Cannot get the step ID for the access_conditions_payment_information pane. The pane is disabled.');
    }

    return $step_id;
  }

}
