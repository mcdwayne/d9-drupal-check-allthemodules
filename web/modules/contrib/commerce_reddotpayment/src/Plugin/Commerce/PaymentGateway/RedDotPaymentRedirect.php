<?php

namespace Drupal\commerce_reddotpayment\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Exception\InvalidResponseException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "reddotpayment_redirect",
 *   label = "Red Dot Payment Redirect",
 *   display_label = "Red Dot Payment Redirect",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_reddotpayment\PluginForm\OffsiteRedirect\RedDotPaymentRedirectForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class RedDotPaymentRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant_id' => '',
      'secret_key' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Merchant ID'),
      '#description' => $this->t('Red Dot Payment Merchant ID given to you when registering for RDP account.'),
      '#default_value' => $this->configuration['merchant_id'],
    ];

    $form['secret_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Secret key'),
      '#description' => $this->t('Red Dot Payment secret key given to you when registering for RDP account.'),
      '#default_value' => $this->configuration['secret_key'],
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
      $this->configuration['secret_key'] = $values['secret_key'];
    }
  }

  /**
   * Return correct payment API endpoint
   *
   * @return string
   */
  public function getPaymentAPIEndpoint() {
    $config = $this->getConfiguration();
    $rdp_endpoint = 'https://secure.reddotpayment.com/service/payment-api';
    if ($config['mode'] == 'test') {
      $rdp_endpoint = 'https://secure-dev.reddotpayment.com/service/payment-api';
    }
    return $rdp_endpoint;
  }

  /**
   * Return correct Merchant processor endpoint
   *
   * @return string
   */
  public function getMerchantProcessorEndpoint() {
    $config = $this->getConfiguration();
    $rdp_endpoint = 'https://secure.reddotpayment.com/service/Merchant_processor/query_redirection';
    if ($config['mode'] == 'test') {
      $rdp_endpoint = 'https://secure-dev.reddotpayment.com/service/Merchant_processor/query_redirection';
    }
    return $rdp_endpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    parent::onCancel($order, $request);
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {
    parent::onNotify($request);

    // Get response
    $transaction_response = json_decode($request->getContent(), true);
    if (empty($transaction_response)) {
      throw new InvalidResponseException('No response returned.');
    }

    $this->handleTransaction($transaction_response);
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    parent::onReturn($order, $request);

    // Validate transaction ID
    $transaction_id = $request->get('transaction_id');
    if (empty($transaction_id)) {
      throw new PaymentGatewayException('Transaction ID not returned.');
    }

    $config = $this->getConfiguration();

    // Validate merchant_id
    if (empty($config['merchant_id'])) {
      throw new PaymentGatewayException('Merchant ID not provided.');
    }

    // Validate secret_key
    if (empty($config['secret_key'])) {
      throw new PaymentGatewayException('Client secret not provided.');
    }

    // Prepare request
    $transaction_request = array(
      'request_mid' => $config['merchant_id'],
      'transaction_id' => $transaction_id
    );

    // Create request signature.
    $transaction_request['signature'] = self::signGeneric($config['secret_key'], $transaction_request);

    // Grab transaction result
    $rdp_endpoint = $this->getMerchantProcessorEndpoint();
    $http = \Drupal::httpClient()
      ->post($rdp_endpoint, [
        'body' => json_encode($transaction_request),
        'http_errors' => FALSE,
        'headers' => [
          'Content-Type' => 'application/json',
        ],
      ]);
    $body = $http->getBody()->getContents();
    $transaction_response = json_decode($body, TRUE);
    if (empty($transaction_response)) {
      throw new InvalidResponseException('No response returned.');
    }

    $this->handleTransaction($transaction_response);
  }

  /**
   * Helper function to hanlde the transaction for both onNotify and onReturn.
   *
   * @param $config
   * @param $order
   * @param $response
   */
  private function handleTransaction($response) {
    $config = $this->getConfiguration();

    // Validate secret_key
    if (empty($config['secret_key'])) {
      throw new PaymentGatewayException('Client secret not provided.');
    }

    // Validate the signature
    if (empty($response['signature'])) {
      throw new InvalidResponseException('No signature returned by the response.');
    }
    $calculated_signature = self::signGeneric($config['secret_key'], $response);
    if ($calculated_signature != $response['signature']) {
      throw new InvalidResponseException('Response signature is invalid!');
    }

    // Validate transaction id
    if (empty($response['transaction_id'])) {
      throw new InvalidResponseException('Transaction ID not provided in response.');
    }

    // Validate order_id
    if (empty($response['order_id'])) {
      throw new InvalidResponseException('Order ID not provided in response.');
    }
    $order = \Drupal::entityTypeManager()->getStorage('commerce_order')->load($response['order_id']);
    if (empty($order)) {
      throw new PaymentGatewayException('Order ID returned from the service not found.');
    }

    // Check if we have a payment matching the transaction ID
    $payments = \Drupal::entityTypeManager()->getStorage('commerce_payment')->loadMultipleByOrder($order);
    $payment = NULL;
    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    foreach($payments as $order_payment) {
      if ($order_payment->getRemoteId() == $response['transaction_id']) {
        $payment = $order_payment;
        break;
      }
    }
    if (empty($payment)) {
      throw new InvalidResponseException('No payments matching returned transaction ID.');
    }

    // Validate response code
    if (!isset($response['response_code'])) {
      throw new InvalidResponseException('No response code.');
    }
    if ($response['response_code'] != 0) {
      throw new DeclineException($this->t('Payment has been declined by the gateway (@error_code).', [
        '@error_code' => $response['response_code'],
      ]), $response['response_code']);
    }

    // Set payment as completed
    $payment->setAuthorizedTime(REQUEST_TIME);
    $payment->setCompletedTime(REQUEST_TIME);
    $payment->setState('completed');
    $payment->save();

    // TODO: Handle authorisations as well, currently only Sale allowed.
  }

  /**
   * Create first phase request signature.
   *
   * @param $secret_key
   * @param $params
   * @return string
   */
  static public function signFirstPhase($secret_key, $params) {
    $fields_for_signature = array('mid', 'order_id', 'payment_type', 'amount', 'ccy');

    $aggregated_fields = "";
    foreach ($fields_for_signature as $f) {
      $aggregated_fields .= trim($params[$f]);
    }
    $aggregated_fields .= $secret_key;

    return hash('sha512', $aggregated_fields);
  }

  /**
   * Create a generic signature.
   *
   * @param $secret_key
   * @param $params
   * @return string
   */
  static public function signGeneric($secret_key, $params) {
    unset($params['signature']);

    $data_to_sign = "";
    self::recursiveGenericArraySign($params, $data_to_sign);
    $data_to_sign .= $secret_key;

    return hash('sha512', $data_to_sign);
  }

  /**
   * Helper recursive function concatenating the data for signature
   *
   * @param $params
   * @param $data_to_sign
   */
  static public function recursiveGenericArraySign(&$params, &$data_to_sign) {
    ksort($params);
    foreach ($params as $v) {
      if (is_array($v)) {
        self::recursiveGenericArraySign($v, $data_to_sign);
      }
      else {
        $data_to_sign .= $v;
      }
    }
  }

}
