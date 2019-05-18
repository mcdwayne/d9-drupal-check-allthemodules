<?php

namespace Drupal\commerce_reddotpayment\PluginForm\OffsiteRedirect;

use Drupal\commerce_payment\Exception\InvalidResponseException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\commerce_reddotpayment\Plugin\Commerce\PaymentGateway\RedDotPaymentRedirect;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

class RedDotPaymentRedirectForm extends BasePaymentOffsiteForm {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;

    /** @var \Drupal\commerce_reddotpayment\Plugin\Commerce\PaymentGateway\RedDotPaymentRedirect $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();
    $config = $payment_gateway_plugin->getConfiguration();
    $order = $payment->getOrder();
    $current_language = \Drupal::languageManager()->getCurrentLanguage();

    /** @var \Drupal\address\AddressInterface $billing_address */
    $billing_address = $order->getBillingProfile()->get('address')->first();

    // Validate merchant_id
    if (empty($config['merchant_id'])) {
      throw new PaymentGatewayException('Merchant ID not provided.');
    }

    // Validate secret_key
    if (empty($config['secret_key'])) {
      throw new PaymentGatewayException('Client secret not provided.');
    }

    // Determine correct endpoint
    $rdp_endpoint = $payment_gateway_plugin->getPaymentAPIEndpoint();

    // Format amount
    $amount = $order->getTotalPrice()->getCurrencyCode() == 'IDR'
      ? floor($order->getTotalPrice()->getNumber())  // Indonesian Rupiah should be sent without digits behind comma
      : sprintf('%0.2f', $order->getTotalPrice()->getNumber());

    // Prepare the first phase request array
    $request = array(
      'mid' => $config['merchant_id'],
      'api_mode' => 'redirection_hosted',
      'payment_type' => 'S', // TODO: Make configurable, please
      'order_id' => $order->id(),
      'store_code' => $order->getStoreId(),
      'ccy' => $order->getTotalPrice()->getCurrencyCode(),
      'amount' => $amount,
      'multiple_method_page' => '1', // TODO: Make configurable, please
      'back_url' => $form['#cancel_url'],
      'redirect_url' => $form['#return_url'],
      'notify_url' => $payment_gateway_plugin->getNotifyUrl()->toString(),
      'locale' => in_array($current_language->getId(), array('en', 'id', 'es', 'fr', 'de')) ? $current_language->getId() : 'en',
      'payer_email' => $order->getEmail(),
      'bill_to_forename' => $billing_address->getGivenName(),
      'bill_to_surname' => $billing_address->getFamilyName(),
      'bill_to_address_city' => $billing_address->getLocality(),
      'bill_to_address_line1' => $billing_address->getAddressLine1(),
      'bill_to_address_line2' => $billing_address->getAddressLine2(),
      'bill_to_address_country' => $billing_address->getCountryCode(),
      'bill_to_address_state' => $billing_address->getAdministrativeArea(),
      'bill_to_address_postal_code' => $billing_address->getPostalCode(),
      'ship_to_forename' => $billing_address->getGivenName(),
      'ship_to_surname' => $billing_address->getFamilyName(),
      'ship_to_address_city' => $billing_address->getLocality(),
      'ship_to_address_line1' => $billing_address->getAddressLine1(),
      'ship_to_address_line2' => $billing_address->getAddressLine2(),
      'ship_to_address_country' => $billing_address->getCountryCode(),
      'ship_to_address_state' => $billing_address->getAdministrativeArea(),
      'ship_to_address_postal_code' => $billing_address->getPostalCode(),
    );

    // Create request signature.
    $request['signature'] = RedDotPaymentRedirect::signFirstPhase($config['secret_key'], $request);

    // Call a service
    $http = \Drupal::httpClient()
      ->post($rdp_endpoint, [
        'body' => json_encode($request),
        'http_errors' => FALSE,
        'headers' => [
          'Content-Type' => 'application/json',
        ],
      ]);
    $body = $http->getBody()->getContents();
    $response = json_decode($body, TRUE);

    // Validate response code
    if (empty($response['signature'])) {
      throw new InvalidResponseException('No signature returned by the response.');
    }
    $calculated_signature = RedDotPaymentRedirect::signGeneric($config['secret_key'], $response);
    if ($calculated_signature != $response['signature']) {
      throw new InvalidResponseException('Invalid signature!');
    }

    // Validate response code
    if (!isset($response['response_code'])) {
      throw new InvalidResponseException('No response code.');
    }
    if ($response['response_code'] != 0) {
      throw new InvalidResponseException('Invalid request: ' . $response['response_msg']);
    }

    // Validate payment URL
    if (empty($response['payment_url'])) {
      throw new InvalidResponseException('Invalid response, no payment_url');
    }

    // Associated payment with the transaction.
    $payment->setRemoteId($response['transaction_id']);
    $payment->save();

    return $this->buildRedirectForm($form, $form_state, $response['payment_url'], array());
  }
}
