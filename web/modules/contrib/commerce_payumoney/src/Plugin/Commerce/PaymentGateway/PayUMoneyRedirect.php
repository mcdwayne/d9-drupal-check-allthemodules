<?php

namespace Drupal\commerce_payumoney\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "payumoney_redirect",
 *   label = "PayUmoney Redirect",
 *   display_label = "PayUmoney Redirect",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_payumoney\PluginForm\PayUMoneyRedirect\PaymentPayUMoneyForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class PayUMoneyRedirect extends OffsitePaymentGatewayBase {


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $pkey = $this->configuration['pkey'];
    $psalt = $this->configuration['psalt'];

    $form['pkey'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Key'),
      '#default_value' => $pkey,
      '#description' => $this->t('Use "gtKFFx" as Test Key'),
      '#required' => TRUE,
    ];
    $form['psalt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant Salt'),
      '#default_value' => $psalt,
      '#description' => $this->t('Use "eCwWELxi" as Test Salt'),
      '#required' => TRUE,
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
      $this->configuration['pkey'] = $values['pkey'];
      $this->configuration['psalt'] = $values['psalt'];
      $this->configuration['pmode'] = $values['pmode'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {

    $additionalCharges = $request->get('additionalCharges');
    $status = $request->get('status');
    $firstname = $request->get('firstname');
    $txnid = $request->get('txnid');
    $amount = $request->get('amount');
    $posted_hash = $request->get('hash');
    $key = $request->get('key');
    $productinfo = $request->get('productinfo');
    $email = $request->get('email');
    $salt = $this->configuration['psalt'];

    if (isset($additionalCharges)) {
      $retHashSeq = $additionalCharges . '|' . $salt . '|' . $status . '|||||||||||' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|' . $txnid . '|' . $key;
    }
    else {
      $retHashSeq = $salt . '|' . $status . '|||||||||||' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|' . $txnid . '|' . $key;
    }

    $hash = hash("sha512", $retHashSeq);

    if ($hash != $posted_hash) {
      drupal_set_message($this->t('Invalid Transaction. Please try again'), 'error');
    }
    else {
      $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
      $payment = $payment_storage->create([
        'state' => $status,
        'amount' => $order->getTotalPrice(),
        'payment_gateway' => $this->entityId,
        'order_id' => $order->id(),
        'test' => $this->getMode() == 'test',
        'remote_id' => $txnid,
        'remote_state' => $request->get('payment_status'),
        'authorized' => $this->time->getRequestTime(),
      ]);
      $payment->save();
      drupal_set_message($this->t('Your payment was successful with Order id : @orderid and Transaction id : @transaction_id', ['@orderid' => $order->id(), '@transaction_id' => $txnid]));
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
