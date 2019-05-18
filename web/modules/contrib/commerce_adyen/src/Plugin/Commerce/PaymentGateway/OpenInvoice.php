<?php

namespace Drupal\commerce_adyen\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment OpenInvoice gateway.
 *
 * @CommercePaymentGateway(
 *   id = "adyen_openinvoice",
 *   label = "Adyen OpenInvoice",
 *   display_label = "Adyen OpenInvoice",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_adyen\PluginForm\OpenInvoicePaymentForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class OpenInvoice extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant_account' => '',
      'client_user' => '',
      'client_password' => '',
      'skin_code' => '',
      'hmac' => '',
      'shopper_locale' => '',
      'recurring' => '',
      'state' => '',
      'use_checkout_form' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['merchant_account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Account'),
      '#default_value' => $this->configuration['merchant_account'],
      '#required' => TRUE,
    ];

    $form['client_user'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client User'),
      '#default_value' => $this->configuration['client_user'],
      '#required' => TRUE,
    ];

    $form['client_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Client Password'),
      '#default_value' => $this->configuration['client_password'],
      '#required' => TRUE,
    ];

    $form['skin_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skin Code'),
      '#default_value' => $this->configuration['skin_code'],
      '#required' => TRUE,
    ];

    $form['hmac'] = [
      '#type' => 'textfield',
      '#title' => $this->t('HMAC Key'),
      '#default_value' => $this->configuration['hmac'],
      '#required' => TRUE,
    ];

    $form['shopper_locale'] = [
      '#type' => 'select',
      '#title' => $this->t('Shopper locale'),
      '#default_value' => $this->configuration['shopper_locale'],
      // @link https://docs.adyen.com/developers/hpp-manual#createaskin
      '#options' => array_map('t', [
        'zh' => 'Chinese – Traditional',
        'cz' => 'Czech',
        'da' => 'Danish',
        'nl' => 'Dutch',
        'en_GB' => 'English – British',
        'en_CA' => 'English – Canadian',
        'en_US' => 'English – US',
        'fi' => 'Finnish',
        'fr' => 'French',
        'fr_BE' => 'French – Belgian',
        'fr_CA' => 'French – Canadian',
        'fr_CH' => 'French – Swiss',
        'fy_NL' => 'Frisian',
        'de' => 'German',
        'el' => 'Greek',
        'hu' => 'Hungarian',
        'it' => 'Italian',
        'li' => 'Lithuanian',
        'no' => 'Norwegian',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'ru' => 'Russian',
        'sk' => 'Slovak',
        'es' => 'Spanish',
        'sv' => 'Swedish',
        'th' => 'Thai',
        'tr' => 'Turkish',
        'uk' => 'Ukrainian',
      ]),
      '#required' => TRUE,
    ];

    $form['recurring'] = [
      '#type' => 'select',
      '#title' => t('Recurring contract'),
      '#empty_option' => t('Do not used'),
      '#default_value' => $this->configuration['recurring'],
      '#options' => [
        'ONECLICK' => t('One click'),
        'RECURRING' => t('Recurring'),
        'ONECLICK,RECURRING' => t('One click, recurring'),
      ],
    ];

    $form['state'] = [
      '#type' => 'select',
      '#title' => t('Fields state'),
      '#default_value' => $this->configuration['state'],
      '#description' => t('State of fields on Adyen HPP.'),
      '#options' => [
        t('Fields are visible and modifiable'),
        t('Fields are visible but unmodifiable'),
        t('Fields are not visible and unmodifiable'),
      ],
    ];

    $form['use_checkout_form'] = [
      '#type' => 'checkbox',
      '#default_value' => $this->configuration['use_checkout_form'],
      '#title' => t('Use checkout forms'),
      '#description' => t('Allow to use checkout forms for filing additional data for the payment type.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant_account'] = $values['merchant_account'];
      $this->configuration['client_user'] = $values['client_user'];
      $this->configuration['client_password'] = $values['client_password'];
      $this->configuration['skin_code'] = $values['skin_code'];
      $this->configuration['hmac'] = $values['hmac'];
      $this->configuration['shopper_locale'] = $values['shopper_locale'];
      $this->configuration['recurring'] = $values['recurring'];
      $this->configuration['state'] = $values['state'];
      $this->configuration['use_checkout_form'] = $values['use_checkout_form'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    // @todo Add examples of request validation.
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'authorization',
      'amount' => $order->getBalance(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'remote_id' => $request->query->get('txn_id'),
      'remote_state' => $request->query->get('payment_status'),
    ]);
    $payment->save();
  }

}
