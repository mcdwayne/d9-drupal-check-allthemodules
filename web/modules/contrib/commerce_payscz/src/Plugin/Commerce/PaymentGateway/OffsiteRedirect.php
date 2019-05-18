<?php

namespace Drupal\commerce_payscz\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_payscz",
 *   label = "Pays.cz",
 *   display_label = @Translation("Card or PayPal"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_payscz\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "discover", "mastercard", "visa",
 *   },
 *   modes = {
 *     "n/a" = @Translation("N/A"),
 *   },
 *   requires_billing_information = FALSE,
 * )
 */
class OffsiteRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant_id' => '',
      'shop_id' => '',
      'merchant_order_number_prefix' => '',
      'send_email' => TRUE,
      'validation_password' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['merchant_id'],
    ];

    $form['shop_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Shop ID'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['shop_id'],
    ];

    $form['validation_password'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Validation password'),
      '#required' => TRUE,
      '#default_value' => $this->configuration['validation_password'],
      '#description' => $this->t('Password you were given by Pays.cz for validating payments confirmations.'),
    ];

    $form['merchant_order_number_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Order number prefix'),
      '#default_value' => $this->configuration['merchant_order_number_prefix'],
      '#description' => $this->t('Prefix for MerchantOrderNumber field what will be send to Pays.cz. If you change it you can brake authorization for payments currently in progress.'),
    ];

    $form['send_email'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Send customer\'s e-mail to gateway'),
      '#description' => $this->t('Keep enabled unless you know why you need to disable it.'),
      '#default_value' => $this->configuration['send_email'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('id') != 'pays') {
      $form_state->setErrorByName('id', t('Machine name must be `pays` because module does not support multiple Pays gateways yet.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['shop_id'] = $values['shop_id'];
      $this->configuration['merchant_order_number_prefix'] = $values['merchant_order_number_prefix'];
      $this->configuration['send_email'] = $values['send_email'];
      $this->configuration['validation_password'] = $values['validation_password'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $q = $request->query->all();

    // Check if some scammer is not trying to pay for another order.
    if (!isset($q['MerchantOrderNumber']) && $q['MerchantOrderNumber'] != $order->id()) {
      throw new PaymentGatewayException('Payment failed!');
    }

    $this->processPaysMessage($q);
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    $this->processPaysMessage($request->query->all());
  }

  /**
   * Process payload from Pays and create or update payment entity.
   */
  protected function processPaysMessage(array $q) {
    // Check if all parameters are there.
    if (!(isset($q['PaymentOrderID']) && isset($q['MerchantOrderNumber']) && isset($q['PaymentOrderStatusID']) && isset($q['CurrencyID']) && isset($q['Amount']) && isset($q['CurrencyBaseUnits']) && isset($q['hash']))) {
      throw new PaymentGatewayException('Payment failed!');
    }

    // Check signature.
    $hashstring = $q['PaymentOrderID'] . $q['MerchantOrderNumber'] . $q['PaymentOrderStatusID'] . $q['CurrencyID'] . $q['Amount'] . $q['CurrencyBaseUnits'];
    $password = $this->configuration['validation_password'];
    if (hash_hmac('md5', $hashstring, $password) !== $q['hash']) {
      throw new PaymentGatewayException('Payment failed!');
    }

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    // Try to find existing payment.
    $payment = $payment_storage->loadByRemoteId($q['PaymentOrderID']);
    // Create if not found.
    if (!$payment) {
      $payment = $payment_storage->create([
        'payment_gateway' => $this->entityId,
        'order_id' => $q['MerchantOrderNumber'],
        'remote_id' => $q['PaymentOrderID'],
      ]);
    }

    $payment->setState(($q['PaymentOrderStatusID'] == '3') ? 'completed' : 'authorization_voided');
    // Use amount from Pays, because scammer can alter it on gateway.
    $payment->setAmount((new Price($q['Amount'], $q['CurrencyID']))->divide($q['CurrencyBaseUnits']));
    // Save only status ID because PaymentOrderStatusDescription is not trustworthy.
    $payment->setRemoteState($q['PaymentOrderStatusID']);

    $payment->save();
  }

}
