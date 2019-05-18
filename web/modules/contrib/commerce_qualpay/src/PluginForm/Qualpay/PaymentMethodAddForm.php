<?php

namespace Drupal\commerce_qualpay\PluginForm\Qualpay;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

class PaymentMethodAddForm extends BasePaymentMethodAddForm {


  const QUALPAY_TEST_URL = 'https://api-test.qualpay.com';

  /**
   * {@inheritdoc}
   */
  public function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    
    // Set our key to settings array.
    /** @var \Drupal\commerce_qualpay\Plugin\Commerce\PaymentGateway\QualpayInterface $plugin */
    $plugin = $this->plugin; 
    // Build a month select list that shows months with a leading zero.
    $months = [];
    for ($i = 1; $i < 13; $i++) {
      $month = str_pad($i, 2, '0', STR_PAD_LEFT);
      $months[$month] = $month;
    }
    // Build a year select list that uses a 4 digit key with a 2 digit value.
    $current_year_4 = date('Y');
    $current_year_2 = date('y');
    $years = [];
    for ($i = 0; $i < 10; $i++) {
      $years[$current_year_4 + $i] = $current_year_2 + $i;
    }

    $element['#attributes']['class'][] = 'credit-card-form';
    // Placeholder for the detected card type. Set by validateCreditCardForm().
    $element['type'] = [
      '#type' => 'hidden',
      '#value' => '',
    ];
    $element['owner'] = [
      '#type' => 'textfield',
      '#title' => t('Card holder Name.'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 30,
      '#size' => 30,
    ];
    $element['number'] = [
      '#type' => 'textfield',
      '#title' => t('Card number'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 19,
      '#size' => 20,
    ];
    $element['expiration'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['credit-card-form__expiration'],
      ],
    ];
    $element['expiration']['month'] = [
      '#type' => 'select',
      '#title' => t('Month'),
      '#options' => $months,
      '#default_value' => date('m'),
      '#required' => TRUE,
    ];
    $element['expiration']['divider'] = [
      '#type' => 'item',
      '#title' => '',
      '#markup' => '<span class="credit-card-form__divider">/</span>',
    ];
    $element['expiration']['year'] = [
      '#type' => 'select',
      '#title' => t('Year'),
      '#options' => $years,
      '#default_value' => $current_year_4,
      '#required' => TRUE,
    ];
    $element['security_code'] = [
      '#type' => 'textfield',
      '#title' => t('CVV'),
      '#attributes' => ['autocomplete' => 'off'],
      '#required' => TRUE,
      '#maxlength' => 4,
      '#size' => 4,
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
    //The JS library performs its own validation.
    $values = $form_state->getValue($element['#parents']);
    $card_type = CreditCard::detectType($values['number']);
    if (!$card_type) {
      $form_state->setError($element['number'], t('You have entered a credit card number of an unsupported card type.'));
      return;
    }
    if (!CreditCard::validateNumber($values['number'], $card_type)) {
      $form_state->setError($element['number'], t('You have entered an invalid credit card number.'));
    }
    if (!CreditCard::validateExpirationDate($values['expiration']['month'], $values['expiration']['year'])) {
      $form_state->setError($element['expiration'], t('You have entered an expired credit card.'));
    }
    if (!CreditCard::validateSecurityCode($values['security_code'], $card_type)) {
      $form_state->setError($element['security_code'], t('You have entered an invalid CVV.'));
    }
    // Persist the detected card type.
    $form_state->setValueForElement($element['type'], $card_type->getId());
  }


  /**
   * {@inheritdoc}
   */
  public function submitCreditCardForm(array $element, FormStateInterface $form_state) {
    
    // The payment gateway plugin will process the submitted payment details.
    $values = $form_state->getValue($element['#parents']);
    //   // $security_key = $this->configuration['security_key'];
  
    $this->entity->card_type = $values['type'];
    $this->entity->card_number = substr($values['number'], -4);
    $this->entity->card_exp_month = $values['expiration']['month'];
    $this->entity->card_exp_year = $values['expiration']['year'];

  }


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    // Add the stripe attribute to the postal code field.
    $form['billing_information']['address']['widget'][0]['address_line1']['#attributes']['data-stripe'] = 'address_line1';
    $form['billing_information']['address']['widget'][0]['address_line2']['#attributes']['data-stripe'] = 'address_line2';
    $form['billing_information']['address']['widget'][0]['locality']['#attributes']['data-stripe'] = 'address_city';
    $form['billing_information']['address']['widget'][0]['postal_code']['#attributes']['data-stripe'] = 'address_zip';
    $form['billing_information']['address']['widget'][0]['country_code']['#attributes']['data-stripe'] = 'address_country';
    return $form;
  }
}
