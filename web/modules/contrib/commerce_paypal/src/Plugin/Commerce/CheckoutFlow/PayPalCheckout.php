<?php

namespace Drupal\commerce_paypal\Plugin\Commerce\CheckoutFlow;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutFlow\CheckoutFlowWithPanesBase;

/**
 * Provides a custom checkout flow for use by PayPal Checkout.
 *
 * @CommerceCheckoutFlow(
 *   id = "paypal_checkout",
 *   label = "PayPal Checkout",
 * )
 */
class PayPalCheckout extends CheckoutFlowWithPanesBase {

  /**
   * {@inheritdoc}
   */
  public function getSteps() {
    // Note that previous_label and next_label are not the labels
    // shown on the step itself. Instead, they are the labels shown
    // when going back to the step, or proceeding to the step.
    return [
      'order_information' => [
        'label' => $this->t('Order information'),
        'has_sidebar' => TRUE,
        'previous_label' => $this->t('Go back'),
      ],
      'review' => [
        'label' => $this->t('Review'),
        'next_label' => $this->t('Continue to review'),
        'previous_label' => $this->t('Go back'),
        'has_sidebar' => TRUE,
      ],
    ] + parent::getSteps();
  }

  /**
   * {@inheritdoc}
   */
  public function getPanes(){
    $panes = parent::getPanes();
    // Create a blacklist of panes we disallow adding to steps.
    $black_list = [
      'contact_information',
      'completion_register',
      'login',
      'payment_information',
      'payment_process',
    ];
    return array_diff_key($panes, array_combine($black_list, $black_list));
  }

}
