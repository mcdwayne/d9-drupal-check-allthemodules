<?php

namespace Drupal\commerce_atom_payment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_atom_payment\AtomEncryption;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "Atom_redirect",
 *   label = "Atom Redirect Payment",
 *   display_label = "Atom Redirect Payment",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_atom_payment\PluginForm\AtomRedirect\PaymentAtomForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class AtomRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $supp_currency = [
      'INR' => 'Indian Rupee',
      'USD' => 'United States Dollar',
      'SGD' => 'Singapore Dollar',
      'GBP' => 'Pound Sterling',
      'EUR' => 'Euro official currency of Eurozone',
    ];
    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Atom Login ID'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];
    $form['access_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Atom Pass Code'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->configuration['access_code'],
      '#required' => TRUE,
    ];
    $form['working_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Atom Request key'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->configuration['working_key'],
      '#required' => TRUE,
    ];
    $form['working_key_res'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Atom Response key'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->configuration['working_key_res'],
      '#required' => TRUE,
    ];
    $form['client_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Atom Client Code'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->configuration['client_code'],
      '#required' => TRUE,
    ];
    $form['product_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Atom Product ID'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $this->configuration['product_id'],
      '#required' => TRUE,
    ];
    $form['currency'] = [
      '#type' => 'select',
      '#title' => $this->t('Default Currency'),
      '#options' => $supp_currency,
      '#default_value' => (isset($this->configuration['working_key'])) ? $this->configuration['working_key'] : 'INR',
    ];
    // @todo : Add supported language dropdown
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
      $this->configuration['working_key_res'] = $values['working_key_res'];
      $this->configuration['currency'] = $values['currency'];
      $this->configuration['language'] = $values['language'];
      $this->configuration['pmode'] = $values['pmode'];
      $this->configuration['client_code'] = $values['client_code'];
      $this->configuration['product_id'] = $values['product_id'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $order_status = "";
    $responseParams = $_POST;
    $encrypt = new AtomEncryption();
    $response_key = \Drupal::config("commerce_payment.commerce_payment_gateway." . $this->entityId)->getRawData()['configuration']['working_key_res'];
    $str = $responseParams["mmp_txn"] . $responseParams["mer_txn"] . $responseParams["f_code"] . $responseParams["prod"] . $responseParams["discriminator"] . $responseParams["amt"] . $responseParams["bank_txn"];
    $signature = $encrypt->signature($str, $response_key);
    if ($signature == $responseParams["signature"] && $responseParams["f_code"] == "Ok") {
      $order_status = 'Success';
    }
    elseif ($responseParams["f_code"] == "F") {
      $order_status = 'Failure';
    }
    elseif ($responseParams["f_code"] == "C") {
      $order_status = 'Aborted';
    }
    else {
      $order_status = "";
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
          'authorized' => REQUEST_TIME,
        ]);
        $payment->save();
        drupal_set_message($this->t('Your payment was successful with Order id : @orderid', ['@orderid' => $order->id()]));
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
    drupal_set_message($this->t('Payment @status on @gateway but may resume the checkout process here when you are ready.', ['@status' => $status, '@gateway' => $this->getDisplayLabel()]), 'error');
  }

}
