<?php

namespace Drupal\commerce_square\PluginForm\Square;

use Drupal\commerce_payment\PluginForm\PaymentMethodAddForm as BasePaymentMethodAddForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Payment method add form for Square.
 */
class PaymentMethodAddForm extends BasePaymentMethodAddForm {

  /**
   * {@inheritdoc}
   */
  public function buildCreditCardForm(array $element, FormStateInterface $form_state) {
    /** @var \Drupal\commerce_square\Plugin\Commerce\PaymentGateway\Square $plugin */
    $plugin = $this->plugin;
    $configuration = $plugin->getConfiguration();
    $config = \Drupal::config('commerce_square.settings');
    $api_mode = ($configuration['mode'] == 'test') ? 'sandbox' : 'production';

    $element['#attached']['library'][] = 'commerce_square/form';
    $element['#attached']['drupalSettings']['commerceSquare'] = [
      'applicationId' => $config->get($api_mode . '_app_id'),
      'drupalSelector' => 'edit-' . str_replace('_', '-', implode('-', $element['#parents'])),
    ];
    $element['#attributes']['class'][] = 'square-form';
    // Populated by the JS library.
    $element['payment_method_nonce'] = [
      '#type' => 'hidden',
      '#attributes' => ['class' => ['square-nonce']],
    ];
    $element['card_type'] = [
      '#type' => 'hidden',
      '#attributes' => ['class' => ['square-card-type']],
    ];
    $element['last4'] = [
      '#type' => 'hidden',
      '#attributes' => ['class' => ['square-last4']],
    ];
    $element['exp_month'] = [
      '#type' => 'hidden',
      '#attributes' => ['class' => ['square-exp-month']],
    ];
    $element['exp_year'] = [
      '#type' => 'hidden',
      '#attributes' => ['class' => ['square-exp-year']],
    ];

    $element['number'] = [
      '#type' => 'item',
      '#title' => t('Card number'),
      '#markup' => '<div id="square-card-number"></div>',
    ];

    $element['details'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => [
          'credit-card-form__expiration',
        ],
      ],
    ];
    $element['details']['expiration'] = [
      '#type' => 'item',
      '#title' => t('Expiration'),
      '#markup' => '<div id="square-expiration-date"></div>',
    ];
    $element['details']['cvv'] = [
      '#type' => 'item',
      '#title' => t('CVV'),
      '#markup' => '<div id="square-cvv"></div>',
    ];
    $element['details']['postal-code'] = [
      '#type' => 'item',
      '#markup' => '<div id="square-postal-code"></div>',
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
