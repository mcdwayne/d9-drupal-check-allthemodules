<?php

namespace Drupal\commerce_smartpay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;


/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_smartpay",
 *   label = @Translation("Smartpay Hosted Payment"),
 *   display_label = @Translation("Credit or Debit card"),
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_smartpay\PluginForm\HostedPaymentForm",
 *   },
 * )
 */


class Smartpay extends OffsitePaymentGatewayBase implements SmartpayInterface  {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant_account' => 'Merchant account',
      'skin_code' => 'Skin code',
      'test_shared_secret' => 'Test shared secret',
      'live_shared_secret' => 'Live shared secret',
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
    $form['skin_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Skin code'),
      '#default_value' => $this->configuration['skin_code'],
    ];
    $form['test_shared_secret'] = [
      '#type' => 'textfield',
      '#title' => t('Test shared secret'),
      '#description' => t('The shared HMAC key for Test mode.'),
      '#default_value' => $this->configuration['test_shared_secret'],
    ];
    $form['live_shared_secret'] = [
      '#type' => 'textfield',
      '#title' => t('Live shared secret'),
      '#description' => t('The shared HMAC key for Live mode.'),
      '#default_value' => $this->configuration['live_shared_secret'],
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
      $this->configuration['skin_code'] = $values['skin_code'];
      $this->configuration['test_shared_secret'] = $values['test_shared_secret'];
      $this->configuration['live_shared_secret'] = $values['live_shared_secret'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $auth_result = $request->query->get('authResult');
    $merchant_reference = $request->query->get('merchantReference');

    if ($auth_result == 'ERROR' || $auth_result == 'CANCELLED') {
      throw new PaymentGatewayException('ERROR result from Smartpay for order ' . $merchant_reference);
    }
    if ($auth_result == 'REFUSED') {
      throw new DeclineException('REFUSED result from Smartpay for order ' . $merchant_reference);
    }
    
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'authorization',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'test' => $this->getMode() == 'test',
      'remote_id' => $merchant_reference,
      'remote_state' => $auth_result,
      'authorized' => $this->time->getRequestTime(),
    ]);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);

    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

    $payment->setState('completed');
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    if ($this->getMode() == 'test') {
      return 'https://test.barclaycardsmartpay.com/hpp/pay.shtml';
    }
    else {
      return 'https://live.barclaycardsmartpay.com/hpp/pay.shtml';
    }
  }

  /**
   * {@inheritdoc}
   */
  public function buildTransaction(PaymentInterface $payment) {
    global $base_url;
    $configuration = $this->getConfiguration();
    $order = $payment->getOrder();
    $amount = $payment->getAmount();

    // Get the test or live HMAC Key.
    if ($this->getMode() == 'test') {
      $hmacKey = $configuration['test_shared_secret'];
    }
    else {
      $hmacKey = $configuration['live_shared_secret'];
    }

    // Build data for transaction.
    $merchantReference = $order->id();
    // Requires value in minor units.
    $paymentAmount = number_format($payment->getAmount()->getNumber()*100, 0, '.', '');
    $currencyCode = $amount->getCurrencyCode();
    $shipBeforeDate = date("Y-m-d", mktime(date("H"), date("i"), date("s"), date("m"), date("j") + 5, date("Y")));
    $sessionValidity = date("c",strtotime("+1 days"));

    $params = [
      'currencyCode'      => $currencyCode,
      'merchantAccount'   => $configuration['merchant_account'],
      'merchantReference' => $merchantReference,
      'paymentAmount'     => $paymentAmount,
      'resURL'            => $base_url . 'checkout/' . $order->id() . '/payment/return',
      'sessionValidity'   => $sessionValidity,
      'shipBeforeDate'    => $shipBeforeDate,
      'shopperEmail'      => $order->getEmail(),
      'shopperReference'  => $order->getCustomerId(),
      'shopperLocale'     => 'en_GB',
      'skinCode'          => $configuration['skin_code'],
    ];

    // The character escape function.
    $escapeVal = function($val) {
      return str_replace(':','\\:',str_replace('\\','\\\\',$val));
    };
    // Sort the array by key using SORT_STRING order.
    ksort($params, SORT_STRING);
    // Generate the signing data string.
    $signData = implode(":",array_map($escapeVal,array_merge(array_keys($params), array_values($params))));
    $merchantSig = base64_encode(hash_hmac('sha256', $signData, pack('H*', $hmacKey), TRUE));
    $params['merchantSig'] = $merchantSig;

    return $params;
  }
  
  
}
