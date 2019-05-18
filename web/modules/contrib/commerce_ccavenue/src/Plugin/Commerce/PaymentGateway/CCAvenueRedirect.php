<?php

namespace Drupal\commerce_ccavenue\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_ccavenue\CCAvenueEncryption;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "ccavenue_redirect",
 *   label = "CCAvenue Redirect",
 *   display_label = "CCAvenue Redirect",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_ccavenue\PluginForm\CCAvenueRedirect\PaymentCCAvenueForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class CCAvenueRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $supp_currency = ['INR' => 'Indian Rupee', 'USD' => 'United States Dollar', 'SGD' => 'Singapore Dollar',
      'GBP' => 'Pound Sterling', 'EUR' => 'Euro, official currency of Eurozone'];
    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant id'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];
    $form['access_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CCavenue access Code'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->configuration['access_code'],
      '#required' => TRUE,
    ];
    $form['working_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CCavenue Working key'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->configuration['working_key'],
      '#required' => TRUE,
    ];
    $form['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Currency'),
      '#options' => $supp_currency,
      '#default_value' => (isset($this->configuration['working_key'])) ? $this->configuration['working_key'] : 'INR',
    ];
    //@todo : Add supported language dropdown
    $form['language'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Language'),
      '#default_value' => 'EN',
    ];
    $form['pmode'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Mode'),
      '#default_value' => $this->getMode(),
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
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['access_code'] = $values['access_code'];
      $this->configuration['working_key'] = $values['working_key'];
      $this->configuration['currency'] = $values['currency'];
      $this->configuration['language'] = $values['language'];
      $this->configuration['pmode'] = $values['pmode'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $order_status = "";
    $encResponse = $request->get('encResp');
    $decrypt = new CCAvenueEncryption();
    $rcvdString = $decrypt->decrypt($encResponse, $this->configuration['working_key']);
    $decryptValues = explode('&', $rcvdString);
    $dataSize = sizeof($decryptValues);

    for ($i = 0; $i < $dataSize; $i++) {
      $information = explode('=', $decryptValues[$i]);
      if ($i == 3)
        $order_status = $information[1];
    }

    switch ($order_status) {

      case 'Success':
        $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
        $payment = $payment_storage->create([
          'state' => 'authorization',
          'amount' => $order->getTotalPrice(),
          'payment_gateway' => $this->entityId,
          'order_id' => $order->id(),
          'test' => $this->getMode() == 'test',
          'remote_id' => $order->id(),
          'remote_state' => $order_status,
          'authorized' => $this->time->getRequestTime(),
        ]);
        $payment->save();
        drupal_set_message($this->t('Your payment was successful with Order id : @orderid and Transaction id : @transaction_id', ['@orderid' => $order->id(), '@transaction_id' => $txnid]));
        break;

      case 'Aborted':
        drupal_set_message($this->t('The transaction has been Aborted.'), 'error');
        break;

      case 'Failure':
        drupal_set_message($this->t('The transaction has been declined.'), 'error');
        break;

      default:
        drupal_set_message($this->t('Security Error. Illegal access detected.'), 'error');
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    $status = $request->get('status');
    drupal_set_message($this->t('Payment @status on @gateway but may resume the checkout process here when you are ready.', [
          '@status' => $status,
          '@gateway' => $this->getDisplayLabel(),
        ]), 'error');
  }

}
