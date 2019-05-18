<?php

namespace Drupal\commerce_dps_pxpay\Plugin\Commerce\PaymentGateway;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\Component\Datetime\TimeInterface;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\XmlEncoder;

/**
 * Provides the DPS PxPay payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "dps_pxpay",
 *   label = "DPS PxPay 2.0",
 *   display_label = "Payment Express",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_dps_pxpay\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "mastercard", "visa"
 *   },
 * )
 */
class OffsiteRedirect extends OffsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->httpClient = \Drupal::httpClient();
    $this->encoder = new XmlEncoder();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'user_id' => '',
      'pxpay_key' => '',
      'test_url' => '',
      'live_url' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['user_id'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('PxPay User ID'),
      '#description' => $this->t('The PxPayUserId is a unique username to identify your customer account.'),
      '#default_value' => $this->configuration['user_id'],
      '#size' => 32,
      '#maxlength' => 32,
    );

    $form['pxpay_key'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('PxPay Key'),
      '#description' => $this->t('The PxPayKey is a unique 64 character key to identify customer account and used to encrypt the transaction request with 3DES to protect the transaction information.'),
      '#default_value' => $this->configuration['pxpay_key'],
      '#size' => 64,
      '#maxlength' => 64,
    );

    $form['test_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Test URL'),
      '#description' => $this->t('The URL to send the requests to when this payment gateway is in Test mode.'),
      '#default_value' => $this->configuration['test_url'] == '' ? "https://uat.paymentexpress.com/pxaccess/pxpay.aspx" : $this->configuration['test_url'] ,
    );

    $form['live_url'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Live URL'),
      '#description' => $this->t('The URL to send the requests to when this payment gateway is in Live mode.'),
      '#default_value' => $this->configuration['live_url'] == '' ? "https://sec.paymentexpress.com/pxaccess/pxpay.aspx" : $this->configuration['live_url'] ,
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['user_id'] = $values['user_id'];
      $this->configuration['pxpay_key'] = $values['pxpay_key'];
      $this->configuration['test_url'] = $values['test_url'];
      $this->configuration['live_url'] = $values['live_url'];
    }
  }

  /**
   * Get the request URL based on the mode.
   *
   * @return string
   *   The request URL.
   */
  protected function requestUrl() {
    $configuration = $this->getConfiguration();
    return $configuration[$configuration['mode'] . '_url'];
  }

  /**
   * Generate the request URL by asking DPS.
   *
   * @param Payment $payment
   *   The payment.
   * @param array $data
   *   Additional data.
   *
   * @return string
   *   The URL to redirect to.
   */
  public function getRedirect(Payment $payment, array $data) {
    // Hey DPS, how are you, can I get a redirect URL?
    $response_contents = '';
    $response = $this->doGenerateRequest($payment, $data);
    $response_decoded = $this->decodeResponse($response);

    // Check if the response is invalid.
    if ($response_decoded['@valid'] == 0) {
      \Drupal::logger('commerce_dps_pxpay')->error('There was a problem with the XML Request: %message.', ['%message' => $response_decoded['URI']]);
      throw new PaymentGatewayException(sprintf('[DPS XML Invalid] %s', $response_decoded['URI']));
    }
    // Check if the request is invalid.
    elseif ($response_decoded['@valid'] == 1 && !isset($response_decoded['URI']) && isset($response_decoded['Reco']) && isset($response_decoded['ResponseText'])) {
      \Drupal::logger('commerce_dps_pxpay')->error('There was a problem with the XML Request: [Reco %code] %message.', [
        '%code' => $response_decoded['Reco'],
        '%message' => $response_decoded['ResponseText'],
      ]);
      throw new PaymentGatewayException(sprintf('[DPS Request Invalid] Code: %s, Response:', $response_decoded['Reco'], $response_decoded['ResponseText']));
    }
    // Check if the URI is in the response.
    elseif (isset($response_decoded['URI'])) {
      $redirect_url = $response_decoded['URI'];
      \Drupal::logger('commerce_dps_pxpay')->info('Redirect URL generated: %url %message.', ['%url' => $response_decoded['URI']]);
    }
    // Fallback.
    else {
      \Drupal::logger('commerce_dps_pxpay')->error('[DPS Missing URI] Valid request but missing URI. Check DPS documentation for changes.');
      throw new PaymentGatewayException(sprintf('[DPS Missing URI] Valid request but missing URI. Check DPS documentation for changes.'));
    }
    return $redirect_url;
  }

  /**
   * Generate the request and return the response.
   *
   * @param PaymentInterface $payment
   *   The payment.
   * @param array $data
   *   Extra data needed for this request.
   *
   * @return Response
   *   The response.
   */
  public function doGenerateRequest(PaymentInterface $payment, array $data) {
    $xml = $this->createGenerateRequestXml($payment, $data);
    $requestUrl = $this->requestUrl();

    // HTTP request to get the redirect.
    $options = [
      'headers' => [
        'Content-type' => 'application/xml',
        'User-Agent' => 'Drupal Commerce DPS PxPay',
      ],
      'body' => $xml,
    ];
    return $this->doRequest('POST', $requestUrl, $options);
  }

  /**
   * Create the XML used to generate the request.
   *
   * @param PaymentInterface $payment
   *   The payment.
   * @param array $data
   *   Extra data needed for this request.
   *
   * @return string
   *   The xml.
   */
  protected function createGenerateRequestXml(PaymentInterface $payment, array $data) {
    $configuration = $this->getConfiguration();
    $user_id = isset($configuration['user_id']) ? $configuration['user_id'] : '';
    $pxpay_key = isset($configuration['pxpay_key']) ? $configuration['pxpay_key'] : '';
    $amount = $this->formatPrice($payment->getAmount()->getNumber());
    $order_id = $payment->getOrderId();

    $customer = $payment->getOrder()->getCustomer();
    $f_name = $customer->get('field_first_name')->first()->getValue();
    $l_name = $customer->get('field_last_name')->first()->getValue();
    $name = $f_name['value'] . ' ' . $l_name['value'];

    $currency = $payment->getOrder()->getStore()->get('default_currency')->first()->getValue();
    $currency = $currency['target_id'];

    $xml = '';
    // Check that all variables are present.
    if ($user_id && $pxpay_key && $amount && $order_id && trim($name) && $currency && isset($data['return']) && isset($data['cancel'])) {
      $xml = '<GenerateRequest>';
      $xml .= '<PxPayUserId>' . $user_id . '</PxPayUserId>';
      $xml .= '<PxPayKey>' . $pxpay_key . '</PxPayKey>';
      $xml .= '<TxnType>Purchase</TxnType>';
      $xml .= '<AmountInput>' . $amount . '</AmountInput>';
      $xml .= '<MerchantReference>' . $order_id . '</MerchantReference>';
      $xml .= '<TxnData1>' . $name . '</TxnData1>';
      $xml .= '<CurrencyInput>' . $currency . '</CurrencyInput>';
      $xml .= '<UrlSuccess>' . $data['return'] . '</UrlSuccess>';
      $xml .= '<UrlFail>' . $data['cancel'] . '</UrlFail>';
      $xml .= '</GenerateRequest>';
      \Drupal::logger('commerce_dps_pxpay')->info('Generate Request XML generated [AmountInput: %amount, MerchantReference: %order_id, Name: %name, Currency: %currency].', [
        '%amount' => $amount,
        '%order_id' => $order_id,
        '%name' => $name,
        '%currency' => $currency,
      ]);
    }
    else {
      \Drupal::logger('commerce_dps_pxpay')->error('Missing values for the Generate Request XML.');
    }

    return $xml;
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {
    $response_decoded = $this->decodeTransactionResult($request);

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    // Check for duplicate transactions.
    // Scenario is when a user clicks back after paying on DPS and
    // clicks the 'NEXT' button on the Hosted Payments Page again.
    $existing_payments = $payment_storage->loadByProperties([
      'state' => 'completed',
      'remote_id' => $response_decoded['TxnId'],
    ]);
    $values = [
      '%order_id' => $order->id(),
      '%currency' => $response_decoded['CurrencySettlement'],
      '%total' => $response_decoded['AmountSettlement'],
      '%txnid' => $response_decoded['TxnId'],
      '%response' => $response_decoded['ResponseText'],
    ];
    if (count($existing_payments) > 0) {
      drupal_set_message('A payment has already been processed and completed.');
      \Drupal::logger('commerce_dps_pxpay')->warning('An existing completed payment with the same DPS transaction ID has been found. No additional payment was created and saved. [Order ID %order_id, Total %currency %total, DPS TxnId %txnid]', $values);
    }
    else {
      $payment = $payment_storage->create([
        'state' => 'completed',
        'amount' => $order->getTotalPrice(),
        'payment_gateway' => $this->entityId,
        'order_id' => $order->id(),
        'remote_id' => $response_decoded['TxnId'],
        'remote_state' => $response_decoded['ResponseText'],
      ]);
      $payment->save();

      drupal_set_message('Payment was processed.');
      \Drupal::logger('commerce_dps_pxpay')->info('The payment was completed. [Order ID %order_id, Total %currency %total, DPS TxnId %txnid, DPS ResponseText %response]', $values);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onCancel(OrderInterface $order, Request $request) {
    $response_decoded = $this->decodeTransactionResult($request);

    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
    $payment = $payment_storage->create([
      'state' => 'authorization',
      'amount' => $order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $order->id(),
      'remote_id' => $response_decoded['TxnId'],
      'remote_state' => $response_decoded['ResponseText'],
    ]);
    // Process payment status received.
    // @todo payment updates if needed.
    // If we didn't get an approval response code...
    $responses = $this->getTransactionResponses();

    if (isset($responses[$response_decoded['ResponseText']])) {
      drupal_set_message($this->t('@response', [
        '@response' => $responses[$response_decoded['ResponseText']],
      ]));
    }
    drupal_set_message($this->t('The transaction was not completed at @gateway but you may resume the checkout process here when you are ready.', [
      '@gateway' => $this->getDisplayLabel(),
    ]));
    \Drupal::logger('commerce_dps_pxpay')->info('There was an incomplete payment. [Order ID %order_id, Total %currency %total, DPS TxnId %txnid, DPS ResponseText %response]', [
      '%order_id' => $order->id(),
      '%currency' => $response_decoded['CurrencySettlement'],
      '%total' => $response_decoded['AmountSettlement'],
      '%txnid' => $response_decoded['TxnId'],
      '%response' => $response_decoded['ResponseText'],
    ]);
  }

  /**
   * Decode the response from DPS after returning from the payment page.
   *
   * @param Request $request
   *   The current URL.
   *
   * @return array
   *   The response content.
   */
  protected function decodeTransactionResult(Request $request) {
    // Find the result data from response.
    $response_string = $request->get('result');

    // If result data is not alphanumeric.
    if (!ctype_alnum($response_string)) {
      $response_string = $response_string . ' (Not alphanumeric)';

      // Create a log.
      \Drupal::logger('commerce_dps_pxpay')->error('The response string is not alphanumeric: %response', ['%response' => $response_string]);
      throw new PaymentGatewayException('The response string is not alphanumeric.');
    }

    $response = $this->doProcessResponse($response_string);
    return $this->decodeResponse($response);
  }

  /**
   * Generate the process response.
   *
   * @param string $response_string
   *   The response string.
   *
   * @return Response
   *   The response.
   */
  protected function doProcessResponse($response_string) {
    $xml = $this->createProcessResponseXml($response_string);
    $requestUrl = $this->requestUrl();

    // HTTP request to get the transaction outcome and details.
    $options = [
      'headers' => [
        'Content-type' => 'application/xml',
        'User-Agent' => 'Drupal Commerce DPS PxPay',
      ],
      'body' => $xml,
    ];
    return $this->doRequest('POST', $requestUrl, $options);
  }

  /**
   * Create the XML used to process the response after to the request.
   *
   * @param string $response_string
   *   The response from DPS.
   *
   * @return string
   *   The xml.
   */
  protected function createProcessResponseXml($response_string) {
    $configuration = $this->getConfiguration();
    $user_id = isset($configuration['user_id']) ? $configuration['user_id'] : '';
    $pxpay_key = isset($configuration['pxpay_key']) ? $configuration['pxpay_key'] : '';

    if ($user_id && $pxpay_key && $response_string) {
      $xml = '<ProcessResponse>';
      $xml .= '<PxPayUserId>' . $user_id . '</PxPayUserId>';
      $xml .= '<PxPayKey>' . $pxpay_key . '</PxPayKey>';
      $xml .= '<Response>' . $response_string . '</Response>';
      $xml .= '</ProcessResponse>';
      \Drupal::logger('commerce_dps_pxpay')->info('Process Response XML generated [Response String: %string].', ['%string' => $response_string]);
    }
    else {
      \Drupal::logger('commerce_dps_pxpay')->error('Missing values for the Process Response XML.');
    }

    return $xml;
  }

  /**
   * Decode the response from DPS into an array.
   *
   * @param Response $response
   *   The response object.
   *
   * @return array
   *   The response values.
   */
  protected function decodeResponse(Response $response) {
    try {
      $response_contents = $response->getBody()->getContents();
    }
    catch (RequestException $e) {
      \Drupal::logger('commerce_dps_pxpay')->error('There was a problem communicating with DPS: %e', ['%e' => $e]);
      throw new PaymentGatewayException(sprintf('[DPS error #%s] %s', $response->getStatusCode(), $response->getReasonPhrase()));
    }

    $xml_options = $this->getXmlContext();
    $response_decoded = $this->encoder->decode($response_contents, 'xml', $xml_options);

    // Check if the response is invalid.
    if ($response_decoded['@valid'] == 0) {
      \Drupal::logger('commerce_dps_pxpay')->error('There was a problem with the XML Request: %message.', ['%message' => $response_decoded['URI']]);
      throw new PaymentGatewayException(sprintf('[DPS XML Invalid] %s', $response_decoded['URI']));
    }
    return $response_decoded;
  }

  /**
   * Do the request.
   *
   * @param string $method
   *   The request method.
   * @param string $requestUrl
   *   The request string.
   * @param array $options
   *   The request options array.
   *
   * @return Response
   *   The response.
   */
  public function doRequest($method, $requestUrl, array $options) {
    try {
      return $this->httpClient->request($method, $requestUrl, $options);
    }
    catch (RequestException $e) {
      \Drupal::logger('commerce_dps_pxpay')->error('There was a problem communicating with DPS: %e', ['%e' => $e]);
      throw new PaymentGatewayException(sprintf('[DPS error #%s] %s', $response->getStatusCode(), $response->getReasonPhrase()));
    }
  }

  /**
   * Convenience function for the XML context.
   *
   * The context keys were taken from https://api.drupal.org/api/drupal/vendor%21symfony%21serializer%21Encoder%21XmlEncoder.php/8.3.x.
   *
   * @return array
   *   An array of xml options and values.
   */
  public function getXmlContext() {
    return [
      'xml_root_node_name' => 'Request',
      'xml_format_output' => FALSE,
      'xml_version' => '1.0',
      'xml_encoding' => 'UTF-8',
      'xml_standalone' => TRUE,
    ];
  }

  /**
   * Returns the price in the dps accepted format.
   *
   * @param int $price
   *   The price.
   *
   * @return string
   *   The price. e.g. "20.00".
   */
  public function formatPrice($price) {
    return (string) number_format((float) $price, 2, '.', '');
  }

  /**
   * Get known transaction responses.
   *
   * @return array
   *   Keys are Response text from DPS, values are human-friendly messages.
   */
  protected function getTransactionResponses() {
    return [
      'DO NOT HONOUR' => 'The transaction was not approved.',
      'INVALID TRANSACTION' => 'The card used does not support this kind of transaction.',
      'DECLINED' => 'The transaction was declined. Funds were not transferred.',
      'CARD EXPIRED' => 'Your card has expired.',
      'DECLINED (U9)' => 'The transaction has not been approved. Please try again.',
    ];
  }

}
