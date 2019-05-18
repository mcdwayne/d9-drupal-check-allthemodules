<?php

namespace Drupal\commerce_payjp\PluginForm\Payjp;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * {@inheritdoc}
 */
class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    $element = parent::buildCreditCardForm($element, $form_state);

    // This ensure the add payment method from user account works.
    $element['#attached']['library'][] = 'commerce_payjp/form';
    // Alter the form with Payjp specific needs.
    $element['#attributes']['class'][] = 'payjp-form';

    // Set our key to settings array.
    /** @var \Drupal\commerce_payjp\Plugin\Commerce\PaymentGateway\PayjpInterface $plugin */
    $plugin = $this->plugin;
    $element['#attached']['drupalSettings']['commercePayjp'] = [
      'publicKey' => $plugin->getPublicKey(),
    ];

    // To display validation errors.
    $element['payment_errors'] = [
      '#type' => 'markup',
      '#markup' => '<div class="payment-errors"></div>',
      '#weight' => -200,
    ];

    // Add class identifiers for card data.
    // Remove required for card data.
    $element['number']['#attributes']['class'][] = 'card-number';
    $element['number']['#required'] = FALSE;
    $element['expiration']['month']['#attributes']['class'][] = 'card-expiry-month';
    $element['expiration']['month']['#required'] = FALSE;
    $element['expiration']['year']['#attributes']['class'][] = 'card-expiry-year';
    $element['expiration']['year']['#required'] = FALSE;
    $element['security_code']['#title'] = t('CVC');
    $element['security_code']['#attributes']['class'][] = 'card-cvc';
    $element['security_code']['#required'] = FALSE;

    // Populated by the JS library.
    $element['payjp_token'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'payjp_token',
      ],
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  protected function validateCreditCardForm(array &$element, FormStateInterface $form_state) {
    // The JS library performs its own validation.
  }

  /**
   * {@inheritdoc}
   */
  public function submitCreditCardForm(array $element, FormStateInterface $form_state) {
    // The payment gateway plugin will process the submitted payment details.
  }

}
