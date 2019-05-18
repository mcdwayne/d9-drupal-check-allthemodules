<?php

namespace Drupal\contacts_events\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentProcess;
use Drupal\contacts_events\Plugin\Commerce\CheckoutFlow\BookingFlow;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Provides the payment process pane.
 *
 * @CommerceCheckoutPane(
 *   id = "booking_payment_process",
 *   label = @Translation("Booking Payment process"),
 *   default_step = "payment",
 *   wrapper_element = "container",
 * )
 */
class BookingPaymentProcess extends PaymentProcess {

  /**
   * {@inheritdoc}
   */
  public function isVisible() {
    // Default behaviour is to hide the payment step if the order
    // has a total of 0. This is no good for us as our bookings always start
    // with a total of 0 and only have a on-zero balance once a ticket is added.
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    // As we override the visibility to always show, we need to continue to
    // the next step if there is still no balance to pay.
    $balance = $this->order->getBalance();
    // If there's nothing to pay, move on.
    if (!$balance || $balance->isZero()) {
      $this->checkoutFlow->redirectToStep($this->checkoutFlow->getNextStepId($this->getStepId()));
    }

    // Otherwise we can pass onto the parent.
    return parent::buildPaneForm($pane_form, $form_state, $complete_form);
  }

  /**
   * Builds the URL to the "error" page.
   *
   * @return \Drupal\Core\Url
   *   The "error" page URL.
   */
  protected function buildErrorUrl() {
    $route_name = $this->checkoutFlow instanceof BookingFlow ?
      $this->checkoutFlow::ROUTE_NAME :
      'commerce_checkout.form';
    return Url::fromRoute($route_name, [
      'commerce_order' => $this->order->id(),
      'step' => $this->getErrorStepId(),
    ], ['absolute' => TRUE]);
  }

  /**
   * Gets the step ID that the customer should be sent to on error.
   *
   * @return string
   *   The error step ID.
   */
  protected function getErrorStepId() {
    // Default to the step that contains the PaymentInformation pane.
    $step_id = $this->checkoutFlow->getPane('booking_payment_information')->getStepId();
    if ($step_id == '_disabled') {
      // Can't redirect to the _disabled step. This could mean that isVisible()
      // was overridden to allow PaymentProcess to be used without a
      // payment_information pane, but this method was not modified.
      throw new \RuntimeException('Cannot get the step ID for the booking_payment_information pane. The pane is disabled.');
    }

    return $step_id;
  }

}
