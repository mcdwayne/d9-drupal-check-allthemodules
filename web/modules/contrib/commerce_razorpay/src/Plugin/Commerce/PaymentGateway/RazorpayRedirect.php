<?php

namespace Drupal\commerce_razorpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\Exception\RequestException;
use Razorpay\Api\Api;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "razorpay_redirect",
 *   label = "Razorpay Redirect",
 *   display_label = "Razorpay Redirect",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_razorpay\PluginForm\OffsiteRedirect\RazorpayForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class RazorpayRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $key_id = $this->configuration['key_id'];
    $key_secret = $this->configuration['key_secret'];

    $form['key_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key Id'),
      '#default_value' => $key_id,
      '#required' => TRUE,
    ];
    $form['key_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Key Secret'),
      '#default_value' => $key_secret,
      '#required' => TRUE,
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
      $this->configuration['key_id'] = $values['key_id'];
      $this->configuration['key_secret'] = $values['key_secret'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {

    $key_id = $this->configuration['key_id'];
    $key_secret = $this->configuration['key_secret'];
    $api = new Api($key_id, $key_secret);
    $payment = $api->order->fetch($order->getData('merchant_order_id'));
    $payment_object = $payment->payments();
    $status = $payment_object['items'][0]->status; // eg : refunded, captured, authorized, failed.
    $refund_status = $payment_object['items'][0]->refund_status; // eg : full, partial
    $amount_refunded = ($payment_object['items'][0]->amount_refunded)/100;
    $service_tax = $payment_object['items'][0]->service_tax;
    $amount = $payment_object['items'][0]->amount;

    // Succeessful.
    $message = '';
    $remote_status = '';
    if ($status == "captured") {
      // Status is success.
      $remote_status = t('Success');
      $message = $this->t('Your payment was successful with Order id : @orderid has been received at : @date', ['@orderid' => $order->id(), '@date' => date("d-m-Y H:i:s", REQUEST_TIME)]);
      $status = COMMERCE_PAYMENT_STATUS_SUCCESS;
    }
    elseif ($status == "authorized") {
      // Batch process - Pending orders.
      $remote_status = t('Pending');
      $message = $this->t('Your payment with Order id : @orderid is pending at : @date', ['@orderid' => $order->id(), '@date' => date("d-m-Y H:i:s", REQUEST_TIME)]);
      $status = COMMERCE_PAYMENT_STATUS_PENDING;
    }
    elseif ($status == "failed") {
      // Failed transaction.
      $remote_status = t('Failure');
      $message = $this->t('Your payment with Order id : @orderid failed at : @date', ['@orderid' => $order->id(), '@date' => date("d-m-Y H:i:s", REQUEST_TIME)]);
      $status = COMMERCE_PAYMENT_STATUS_FAILURE;
    }

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
        'state' => $status,
        'amount' => $order->getTotalPrice(),
        'payment_gateway' => $this->entityId,
        'order_id' => $order->id(),
        'test' => $this->getMode() == 'test',
        'remote_id' => $payment_object['items'][0]->id,
        'remote_state' => $remote_status ? $remote_status : $request->get('payment_status'),
        'authorized' => REQUEST_TIME,
      ]
    );

    $payment->save();
    drupal_set_message($message);

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


  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {

    // Get configuration values.
    $key_id = $this->configuration['key_id'];
    $key_secret = $this->configuration['key_secret'];
    $api = new Api($key_id, $key_secret);

    if (empty($payment->getRemoteId())) {
      throw new InvalidRequestException('Could not determine the remote payment details.');
    }
    // At present remote id razorpay payment id.
    $razorpay_payment_id = $payment->getRemoteId();
    $razorpay_payment = $api->payment->fetch($razorpay_payment_id);

    if (!in_array($payment->getState()->value, ['capture_completed', 'capture_partially_refunded'])) {
      throw new \InvalidArgumentException('Only payments in the "capture_completed" and "capture_partially_refunded" states can be refunded.');
    }

    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();

    // Validate the requested amount.
    $balance = $payment->getBalance();
    if ($amount->greaterThan($balance)) {
      throw new InvalidRequestException(sprintf("Can't refund more than %s.", $balance->__toString()));
    }

    try {
      $old_refunded_amount = $payment->getRefundedAmount();
      $new_refunded_amount = $old_refunded_amount->add($amount);

      if ($new_refunded_amount->lessThan($payment->getAmount())) {
        $payment->state = 'capture_partially_refunded';
        $razorpay_payment->refund();
      }
      else {
        $payment->state = 'capture_refunded';
        $razorpay_payment->refund(array('amount' => $new_refunded_amount* 100)); // for partial refund
      }

      $payment->setRefundedAmount($new_refunded_amount)->save();
    }
    catch (RequestException $e) {
      throw new InvalidRequestException("Could not refund the payment.", $e->getCode(), $e);
    }

  }


}
