<?php

namespace Drupal\paypal_subscriptions\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_paypal\Plugin\Commerce\PaymentGateway\ExpressCheckout;

/**
 * Provides the Paypal Express Checkout payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "paypal_express_checkout_subscription",
 *   label = @Translation("PayPal recurring (Express Checkout)"),
 *   display_label = @Translation("PayPal"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_paypal\PluginForm\ExpressCheckoutForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "discover", "mastercard", "visa",
 *   },
 * )
 */
class ExpressCheckoutSubscriptions extends ExpressCheckout {


  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['billing_period'] = [
      '#type' => 'select',
      '#title' => $this->t('Select Billing Period'),
      '#options' => [
        'Day' => $this->t('Day'),
        'Week' => $this->t('Week'),
        'SemiMonth' => $this->t('SemiMonth'),
        'Month' => $this->t('Month'),
        'Year' => $this->t('Year'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    if (!$form_state->getErrors() && $form_state->isSubmitted()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['billing_period'] = $values['billing_period'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['billing_period'] = $values['billing_period'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setExpressCheckout(PaymentInterface $payment, array $extra) {
    $order = $payment->getOrder();

    $amount = $this->rounder->round($payment->getAmount());

    // Build a name-value pair array for this transaction.
    $nvp_data = [
      'METHOD' => 'SetExpressCheckout',

      // Default the Express Checkout landing page to the Mark solution.
      'SOLUTIONTYPE' => 'Mark',
      'LANDINGPAGE' => 'Login',

      // Disable entering notes in PayPal, we don't have any way to accommodate
      // them right now.
      'ALLOWNOTE' => '0',

      'AMT' => $amount->getNumber(),
      'CURRENCYCODE' => $amount->getCurrencyCode(),
      'PAYMENTACTION' => 'Sale',
      'INVNUM' => $order->id(),

      'L_BILLINGTYPE0' => 'RecurringPayments',
      'L_BILLINGAGREEMENTDESCRIPTION0' => 'Subscription',

      // Set the return and cancel URLs.
      'RETURNURL' => $extra['return_url'],
      'CANCELURL' => $extra['cancel_url'],
    ];

    // Add itemized information to the API request.
    $nvp_data += $this->itemizeOrder($order, $amount->getCurrencyCode());

    // Send the order's email if not empty.
    if (!empty($order->getEmail())) {
      $nvp_data['PAYMENTREQUEST_0_EMAIL'] = $order->getEmail();
    }

    // Make the PayPal NVP API request.
    return $this->doRequest($nvp_data, $order);
  }

  /**
   * {@inheritdoc}
   */
  public function getExpressCheckoutDetails(OrderInterface $order) {
    // Get the Express Checkout order token.
    $order_express_checkout_data = $order->getData('paypal_express_checkout_recurring');

    // Build a name-value pair array to obtain buyer information from PayPal.
    $nvp_data = [
      'METHOD' => 'GetExpressCheckoutDetails',
      'TOKEN' => $order_express_checkout_data['token'],
    ];

    // Make the PayPal NVP API request.
    return $this->doRequest($nvp_data, $order);

  }

  /**
   * {@inheritdoc}
   */
  public function doExpressCheckoutDetails(OrderInterface $order) {
    // Build NVP data for PayPal API request.
    $order_express_checkout_data = $order->getData('paypal_express_checkout_recurring');
    $amount = $this->rounder->round($order->getTotalPrice());
    $configuration = $this->getConfiguration();

    $nvp_data = [
      'METHOD' => 'CreateRecurringPaymentsProfile',
      'TOKEN' => $order_express_checkout_data['token'],
      'PAYERID' => $order_express_checkout_data['payerid'],
      "PROFILESTARTDATE" => date("Y-m-d\TH:i:s\Z"),
      "DESC" => "Subscription",
      "BILLINGPERIOD" => $configuration['billing_period'],
      "BILLINGFREQUENCY" => "1",
      "AMT" => $amount->getNumber(),
      "CURRENCYCODE" => $amount->getCurrencyCode(),
    ];

    // Make the PayPal NVP API request.
    return $this->doRequest($nvp_data, $order);

  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $order_express_checkout_data = $order->getData('paypal_express_checkout_recurring');
    if (empty($order_express_checkout_data['token'])) {
      throw new PaymentGatewayException('Token data missing for this PayPal Express Checkout transaction.');
    }

    // GetExpressCheckoutDetails API Operation (NVP).
    // Shows information about an Express Checkout transaction.
    $paypal_response = $this->getExpressCheckoutDetails($order);

    // If the request failed, exit now with a failure message.
    if ($paypal_response['ACK'] == 'Failure') {
      throw new PaymentGatewayException($paypal_response['PAYMENTREQUESTINFO_0_LONGMESSAGE'], $paypal_response['PAYMENTREQUESTINFO_n_ERRORCODE']);
    }

    // Set the Payer ID used to finalize payment.
    $order_express_checkout_data['payerid'] = $paypal_response['PAYERID'];
    $order->setData('paypal_express_checkout_recurring', $order_express_checkout_data);

    // If the user is anonymous, add their PayPal e-mail to the order.
    if (empty($order->mail)) {
      $order->setEmail($paypal_response['EMAIL']);
    }
    $order->save();

    // DoExpressCheckoutPayment API Operation (NVP).
    // Completes an Express Checkout transaction.
    $paypal_response = $this->doExpressCheckoutDetails($order);

    // Nothing to do for failures for now - no payment saved.
    // @todo - more about the failures.
    if ($paypal_response['PROFILESTATUS'] !== 'ActiveProfile') {
      throw new PaymentGatewayException($paypal_response['PAYMENTINFO_0_LONGMESSAGE'], $paypal_response['PAYMENTINFO_0_ERRORCODE']);
    }

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'authorization',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'remote_id' => $paypal_response['PROFILEID'],
      'remote_state' => $paypal_response['PROFILESTATUS'],
    ]);

    $payment->save();
  }

}
