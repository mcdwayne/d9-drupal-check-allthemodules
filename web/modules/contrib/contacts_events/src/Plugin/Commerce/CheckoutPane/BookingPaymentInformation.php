<?php

namespace Drupal\contacts_events\Plugin\Commerce\CheckoutPane;

use Drupal\commerce_checkout\Plugin\Commerce\CheckoutPane\CheckoutPaneInterface;
use Drupal\commerce_payment\Plugin\Commerce\CheckoutPane\PaymentInformation;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the review pane.
 *
 * @CommerceCheckoutPane(
 *   id = "booking_payment_information",
 *   label = @Translation("Booking payment information"),
 *   default_step = "review",
 * )
 */
class BookingPaymentInformation extends PaymentInformation implements CheckoutPaneInterface {

  /**
   * {@inheritdoc}
   */
  public function buildPaneSummary() {
    return $this->getSummary();
  }

  /**
   * Ge the payment summary information.
   *
   * @return array
   *   The render array.
   */
  public function getSummary() {
    $default_currency = $this->order->getStore()->getDefaultCurrencyCode();
    $total = $this->order->getTotalPrice() ?: new Price(0, $default_currency);
    $paid = $this->order->getTotalPaid() ?: new Price(0, $default_currency);
    $balance = $this->order->getBalance() ?: new Price(0, $default_currency);

    return [
      '#type' => 'html_tag',
      '#tag' => 'dl',
      'total_title' => [
        '#type' => 'html_tag',
        '#tag' => 'dt',
        '#value' => $this->t('Booking total'),
      ],
      'total_value' => [
        '#type' => 'html_tag',
        '#tag' => 'dt',
        '#value' => $total,
      ],
      'paid_title' => [
        '#type' => 'html_tag',
        '#tag' => 'dt',
        '#value' => $this->t('Paid so far'),
      ],
      'paid_value' => [
        '#type' => 'html_tag',
        '#tag' => 'dt',
        '#value' => $paid,
      ],
      'balance_title' => [
        '#type' => 'html_tag',
        '#tag' => 'dt',
        '#value' => $this->t('Outstanding balance'),
      ],
      'balance_value' => [
        '#type' => 'html_tag',
        '#tag' => 'dt',
        '#value' => $balance,
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPaneForm(array $pane_form, FormStateInterface $form_state, array &$complete_form) {
    $pane_form['into']['title'] = [
      '#type' => 'html_tag',
      '#tag' => 'h2',
      '#value' => $this->t('Payment'),
    ];
    $pane_form['into']['text'] = [
      '#type' => 'html_tag',
      '#tag' => 'p',
    ];

    // Adjust the text based on the next action.
    if (isset($complete_form['actions']['next']) && $complete_form['actions']['next']['#type'] == 'submit') {
      $pane_form['into']['text']['#value'] = $this->t('To complete your booking, please check the details and click the %label button.', [
        '%label' => $complete_form['actions']['next']['#value'],
      ]);
    }
    else {
      $pane_form['into']['text']['#value'] = $this->t('Your booking is confirmed and there is nothing to pay.');
    }

    $pane_form['balances'] = $this->getSummary();

    return parent::buildPaneForm($pane_form, $form_state, $complete_form);
  }

  /**
   * {@inheritdoc}
   */
  protected function buildBillingProfileForm(array $pane_form, FormStateInterface $form_state) {
    // We already have the billing details from a previous step, but the submit
    // handler requires the billing profile in the form array.
    $pane_form['billing_information']['#profile'] = $this->order->getBillingProfile();
    return $pane_form;
  }

}
