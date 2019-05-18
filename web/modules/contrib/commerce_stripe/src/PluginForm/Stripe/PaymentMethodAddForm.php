<?php

namespace Drupal\commerce_stripe\PluginForm\Stripe;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    // Alter the form with Stripe specific needs.
    $element['#attributes']['class'][] = 'stripe-form';

    // Set our key to settings array.
    /** @var \Drupal\commerce_stripe\Plugin\Commerce\PaymentGateway\StripeInterface $plugin */
    $plugin = $this->plugin;
    $element['#attached']['library'][] = 'commerce_stripe/form';
    $element['#attached']['drupalSettings']['commerceStripe'] = [
      'publishableKey' => $plugin->getPublishableKey(),
    ];

    // Populated by the JS library.
    $element['stripe_token'] = [
      '#type' => 'hidden',
      '#attributes' => [
        'id' => 'stripe_token'
      ]
    ];

    $element['card_number'] = [
      '#type' => 'item',
      '#title' => t('Card number'),
      '#required' => TRUE,
      '#validated' => TRUE,
      '#markup' => '<div id="card-number-element" class="form-text"></div>',
    ];

    $element['expiration'] = [
      '#type' => 'item',
      '#title' => t('Expiration date'),
      '#required' => TRUE,
      '#validated' => TRUE,
      '#markup' => '<div id="expiration-element"></div>',
    ];

    $element['security_code'] = [
      '#type' => 'item',
      '#title' => t('CVC'),
      '#required' => TRUE,
      '#validated' => TRUE,
      '#markup' => '<div id="security-code-element"></div>',
    ];

    // To display validation errors.
    $element['payment_errors'] = [
      '#type' => 'markup',
      '#markup' => '<div id="payment-errors"></div>',
      '#weight' => -200,
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

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['billing_information']['#after_build'][] = [get_class($this), 'addAddressAttributes'];

    return $form;
  }

  /**
   * Element #after_build callback: adds "data-stripe" to address properties.
   *
   * This allows our JavaScript to pass these values to Stripe as customer
   * information, enabling CVC, Zip, and Street checks.
   *
   * @param array $element
   *   The form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The modified form element.
   */
  public static function addAddressAttributes($element, FormStateInterface $form_state) {
    $element['address']['widget'][0]['address']['given_name']['#attributes']['data-stripe'] = 'given_name';
    $element['address']['widget'][0]['address']['family_name']['#attributes']['data-stripe'] = 'family_name';
    $element['address']['widget'][0]['address']['address_line1']['#attributes']['data-stripe'] = 'address_line1';
    $element['address']['widget'][0]['address']['address_line2']['#attributes']['data-stripe'] = 'address_line2';
    $element['address']['widget'][0]['address']['locality']['#attributes']['data-stripe'] = 'address_city';
    $element['address']['widget'][0]['address']['postal_code']['#attributes']['data-stripe'] = 'address_zip';
    // Country code is a sub-element and needs another callback.
    $element['address']['widget'][0]['address']['country_code']['#pre_render'][] = [get_called_class(), 'addCountryCodeAttributes'];

    return $element;
  }

  /**
   * Element #pre_render callback: adds "data-stripe" to the country_code.
   *
   * This ensures data-stripe is on the hidden or select element for the country
   * code, so that it is properly passed to Stripe.
   *
   * @param array $element
   *   The form element.
   *
   * @return array
   *   The modified form element.
   */
  public static function addCountryCodeAttributes($element) {
    $element['country_code']['#attributes']['data-stripe'] = 'address_country';
    return $element;
  }

}
