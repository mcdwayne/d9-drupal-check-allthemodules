<?php

namespace Drupal\commerce_payu_india\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_payu_india_redirect",
 *   label = "Commerce PayU India Redirect",
 *   display_label = "Commerce PayU India Redirect",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_payu_india\PluginForm\CommercePayUIndiaRedirect\PaymentCommercePayUIndiaForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class CommercePayUIndia extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $pkey = $this->configuration['key'];
    $psalt = $this->configuration['salt'];

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key'),
      '#default_value' => $pkey,
      '#description' => $this->t('Your payU Merchant Key.'),
      '#required' => TRUE,
    ];
    $form['salt'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Salt'),
      '#default_value' => $psalt,
      '#description' => $this->t('Your payU account Salt.'),
      '#required' => TRUE,
    ];
    $form['payment_mode'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Payment Mode'),
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
      $this->configuration['key'] = $values['key'];
      $this->configuration['salt'] = $values['salt'];
      $this->configuration['payment_mode'] = $values['payment_mode'];
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
    $salt = $this->configuration['salt'];
    $hash_data['key'] = $request->get('key');
    $hash_data['txnid'] = $request->get('txnid');
    $hash_data['amount'] = $request->get('amount');
    $hash_data['productinfo'] = $request->get('productinfo');
    $hash_data['firstname'] = $request->get('firstname');
    $hash_data['email'] = $request->get('email');
    if ($request->get('additionalCharges')) {
      $hash_data['additional_charges'] = $request->get('additionalCharges');
    }
    $service = \Drupal::service('commerce_payu_india.calculate_hash');
    $hash = $service->commercePayUIndiaReverseHash($hash_data, $salt, $status);

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
