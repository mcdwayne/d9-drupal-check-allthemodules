<?php

namespace Drupal\commerce_payeezy\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\DeclineException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use GuzzleHttp\Exception\RequestException;

/**
 * Provides Payeezy On-site payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "commerce_payeezy_onsite_gateway",
 *   label = "Payeezy onsite gateway",
 *   display_label = "Payeezy onsite gateway",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_payeezy\PluginForm\PayeezyOnsiteGateway\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class PayeezyOnsiteGateway extends OnsitePaymentGatewayBase implements PayeezyGatewayInterface {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'api_key' => '',
      'api_secret_key' => '',
      'merchant_token' => '',
      'transaction_url' => '',
      'js_security_key' => '',
      'ta_token' => 'NOIW',
      'callback' => 'Payeezy.callback',
      'token_type' => 'FDtoken',
      'security_token_url' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // As per schema.yml file.
    $form['api_key'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Get your key from https://developer.payeezy.com/'),
      '#title' => $this->t('API key'),
      '#default_value' => $this->configuration['api_key'],
      '#required' => TRUE,
    ];
    $form['api_secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API secret'),
      '#description' => $this->t('Get your key from https://developer.payeezy.com/'),
      '#default_value' => $this->configuration['api_secret_key'],
      '#required' => TRUE,
    ];
    $form['merchant_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant token'),
      '#description' => $this->t('Get your token from https://developer.payeezy.com/'),
      '#default_value' => $this->configuration['merchant_token'],
      '#required' => TRUE,
    ];
    $form['transaction_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Transaction URL'),
      '#description' => $this->t('Usually https://api-cert.payeezy.com/v1/transactions for Sandbox & https://api.payeezy.com/v1/transactions for Production'),
      '#default_value' => $this->configuration['transaction_url'],
      '#required' => TRUE,
    ];
    $form['js_security_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('JS security key'),
      '#description' => $this->t('Merchant js_security_key from https://developer.payeezy.com/user/me/merchants'),
      '#default_value' => $this->configuration['js_security_key'],
      '#required' => TRUE,
    ];
    $form['ta_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('TA token'),
      '#description' => $this->t('For sandbox, the value is NOIW. For live, this is the Transarmor Token under Terminals tab in Administration.'),
      '#default_value' => $this->configuration['ta_token'],
      '#required' => TRUE,
    ];
    $form['token_type'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Token type'),
      '#default_value' => $this->configuration['token_type'],
      '#required' => TRUE,
    ];
    $form['callback'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Callback'),
      '#description' => $this->t('Leave this as is, if you are not sure.'),
      '#default_value' => $this->configuration['callback'],
      '#required' => TRUE,
    ];
    $form['security_token_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Security token url'),
      '#description' => $this->t('Usually for Sandbox, it is https://api-cert.payeezy.com/v1/securitytokens'),
      '#default_value' => $this->configuration['security_token_url'],
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
      $this->configuration['api_key'] = $values['api_key'];
      $this->configuration['api_secret_key'] = $values['api_secret_key'];
      $this->configuration['merchant_token'] = $values['merchant_token'];
      $this->configuration['transaction_url'] = $values['transaction_url'];
      $this->configuration['js_security_key'] = $values['js_security_key'];
      $this->configuration['ta_token'] = $values['ta_token'];
      $this->configuration['token_type'] = $values['token_type'];
      $this->configuration['security_token_url'] = $values['security_token_url'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);
    $currency_code = $payment->getAmount()->getCurrencyCode();

    // Payeezy accepts amount in cents, so to charge $1,
    // the chargeable amount that Payeezy accepts is 100 cents.
    $amount = $payment->getAmount()->multiply(100)->getNumber();

    // TODO: add metadata, if nay by other modules.
    if ((int) $amount > 0) {

      // Prepare post data details.
      $post_data = [
        'merchant_ref' => $this->t('Order Number: @order_number', ['@order_number' => $payment->getOrderId()]),
        'transaction_type' => 'purchase',
        'method' => 'token',
        'amount' => $amount,
        'currency_code' => $currency_code,
        'token' => json::decode($payment_method->remote_id->value),
      ];

      $transaction_url = $this->configuration['transaction_url'];
      $response = $this->payeezyPost($payment, $post_data, $transaction_url);

      if (isset($response['transaction_status']) && $response['transaction_status'] == 'approved') {

        // We need to keep track of transaction_id and transaction_tag both.
        $remote_id = 'ID: ' . $response['transaction_id'] . ', Tag:' . $response['transaction_tag'];
        $payment->state = $capture ? 'capture_completed' : 'authorization';

        $payment->setRemoteId($remote_id);
        $payment->setAuthorizedTime(\Drupal::time()->getRequestTime());
        if ($capture) {
          $payment->setState('completed');
        }
        $payment->save();

        drupal_set_message($this->t('Your payment was successful with Order ID : @orderid and Transaction ID : @transaction_id', [
          '@orderid' => $payment->getOrderId(),
          '@transaction_id' => $response['transaction_tag'],
        ]));
      }
      else {
        \Drupal::logger('commerce_payeezy')->error(t('Payment could not be processed'));
        throw new DeclineException();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    $amount = $amount ?: $payment->getAmount();
    $currency_code = $payment->getAmount()->getCurrencyCode();

    // Payeezy accepts amount in cents, so to charge $1,
    // the chargeable amount that Payeezy accepts is 100 cents.
    $capture_amount = $amount->multiply(100)->getNumber();

    if ((int) $capture_amount > 0) {
      $remote_id = $payment->getRemoteId();
      $remote_id = explode(', ', $remote_id);
      $transaction_id = str_replace('ID:', '', $remote_id[0]);
      $transaction_tag = str_replace('Tag:', '', $remote_id[1]);

      // Prepare post data details.
      $post_data = [
        'merchant_ref' => $this->t('Capture - Order Number: @order_number', ['@order_number' => $payment->getOrderId()]),
        'transaction_tag' => $transaction_tag,
        'transaction_type' => 'capture',
        'method' => 'credit_card',
        'amount' => $capture_amount,
        'currency_code' => $currency_code,
      ];

      $transaction_url = $this->configuration['transaction_url'] . '/' . $transaction_id;
      $response = $this->payeezyPost($payment, $post_data, $transaction_url);

      if (isset($response['transaction_status']) && $response['transaction_status'] == 'approved') {
        $payment->state = 'capture_completed';
        $payment->setAmount($amount);
        $payment->setState('completed');
        $payment->save();
      }
      else {
        \Drupal::logger('commerce_payeezy')->error(t('Amount could not be captured.'));
        throw new DeclineException();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);
    $currency_code = $payment->getAmount()->getCurrencyCode();

    // Payeezy accepts amount in cents, so to charge $1,
    // the chargeable amount that Payeezy accepts is 100 cents.
    $amount = $payment->getAmount();
    $amount = $amount->multiply(100)->getNumber();

    if ((int) $amount > 0) {
      $remote_id = $payment->getRemoteId();
      $remote_id = explode(', ', $remote_id);
      $transaction_id = str_replace('ID:', '', $remote_id[0]);
      $transaction_tag = str_replace('Tag:', '', $remote_id[1]);

      // Prepare post data details.
      $post_data = [
        'merchant_ref' => $this->t('Void - Order Number: @order_number', ['@order_number' => $payment->getOrderId()]),
        'transaction_tag' => $transaction_tag,
        'transaction_type' => 'void',
        'method' => 'credit_card',
        'amount' => $amount,
        'currency_code' => $currency_code,
      ];

      $transaction_url = $this->configuration['transaction_url'] . '/' . $transaction_id;
      $response = $this->payeezyPost($payment, $post_data, $transaction_url);

      if (isset($response['transaction_status']) && $response['transaction_status'] == 'approved') {
        $payment->state = 'authorization_voided';
        $payment->save();
      }
      else {
        \Drupal::logger('commerce_payeezy')->error(t('Amount could not be captured.'));
        throw new DeclineException();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['capture_completed', 'capture_partially_refunded']);
    $amount = $amount ?: $payment->getAmount();
    $payment_method = $payment->getPaymentMethod();
    $currency_code = $payment->getAmount()->getCurrencyCode();
    $this->assertRefundAmount($payment, $amount);

    // Payeezy accepts amount in cents, so to charge $1,
    // the chargeable amount that Payeezy accepts is 100 cents.
    $refund_amount = $amount->multiply(100)->getNumber();

    if ((int) $refund_amount > 0) {

      $remote_id = $payment->getRemoteId();
      $remote_id = explode(', ', $remote_id);
      $transaction_id = str_replace('ID:', '', $remote_id[0]);
      $transaction_tag = str_replace('Tag:', '', $remote_id[1]);

      // Prepare post data details.
      $post_data = [
        'merchant_ref' => $this->t('Refund - Order Number: @order_number', ['@order_number' => $payment->getOrderId()]),
        'transaction_type' => 'refund',
        'transaction_tag' => $transaction_tag,
        'method' => 'token',
        'amount' => $refund_amount,
        'currency_code' => $currency_code,
        'token' => json::decode($payment_method->remote_id->value),
      ];

      $transaction_url = $this->configuration['transaction_url'] . '/' . $transaction_id;
      $response = $this->payeezyPost($payment, $post_data, $transaction_url);

      if (isset($response['transaction_status']) && $response['transaction_status'] == 'approved') {
        $old_refunded_amount = $payment->getRefundedAmount();
        $new_refunded_amount = $old_refunded_amount->add($amount);

        if ($new_refunded_amount->lessThan($payment->getAmount())) {
          $payment->state = 'capture_partially_refunded';
        }
        else {
          $payment->state = 'capture_refunded';
        }

        $payment->setRefundedAmount($new_refunded_amount);
        $payment->save();
      }
      else {
        \Drupal::logger('commerce_payeezy')->error(t('Amount could not be refunded.'));
        throw new DeclineException();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $remote_payment_method = $this->doCreatePaymentMethod($payment_method, $payment_details);

    if (count($remote_payment_method) > 0) {
      $token = Json::encode($remote_payment_method['token']);

      $payment_method->card_type = $remote_payment_method['card_type'];
      $payment_method->card_number = $remote_payment_method['last4'];
      $payment_method->card_exp_month = $remote_payment_method['expiration_month'];
      $payment_method->card_exp_year = $remote_payment_method['expiration_year'];
      $payment_method->setRemoteId($token);
      $expires = CreditCard::calculateExpirationTimestamp($remote_payment_method['expiration_month'], $remote_payment_method['expiration_year']);
      $payment_method->setExpiresTime($expires);

      $payment_method->save();
    }
    else {
      \Drupal::logger('commerce_payeezy')->error(t('Merchant unable to generate security token. Verify Transarmor token, and js_security_key values.'));
      throw new DeclineException();
    }
  }

  /**
   * Get a security token from payment gateway.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   * @param array $payment_details
   *   The gateway-specific payment details.
   *
   * @return array
   *   The payment method information returned by the gateway. Notable keys:
   *   - token: The remote ID.
   *   Credit card specific keys:
   *   - card_type: The card type.
   *   - last4: The last 4 digits of the credit card number.
   *   - expiration_month: The expiration month.
   *   - expiration_year: The expiration year.
   */
  protected function doCreatePaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $card_type = CreditCard::detectType($payment_details['number'])->getId();
    $exp_month = $payment_details['expiration']['month'];
    $exp_year = substr($payment_details['expiration']['year'], -2);
    $billing_profile = $payment_method->getBillingProfile()->address->first();

    // Prepare transaction details.
    $data = [
      'apikey' => $this->configuration['api_key'],
      'js_security_key' => $this->configuration['js_security_key'],
      'ta_token' => $this->configuration['ta_token'],
      'callback' => $this->configuration['callback'],
      'type' => $this->configuration['token_type'],
      'credit_card.type' => $card_type,
      'credit_card.cardholder_name' => $payment_details['card_owner_name'],
      'credit_card.card_number' => $payment_details['number'],
      'credit_card.exp_date' => $exp_month . $exp_year,
      'credit_card.cvv' => $payment_details['security_code'],
      'billing_address.city' => $billing_profile->getLocality(),
      'billing_address.country' => $billing_profile->getCountryCode(),
      'billing_address.state_province' => $billing_profile->getAdministrativeArea(),
      'billing_address.zip_postal_code' => $billing_profile->getPostalCode(),
    ];

    $url = $this->configuration['security_token_url'];
    $url = Url::fromUri($url, ['absolute' => TRUE, 'query' => $data])->toString();

    // Generate a security token from Payeezy.
    try {
      $response = \Drupal::httpClient()->get($url)->getBody()->getContents();

      // Remove json padding and decode to array.
      $response = str_replace('Payeezy.callback(', '', $response);
      $response = substr($response, 0, strpos($response, ')'));
      $response = Json::decode($response);
      $results = $response['results'];

      if (isset($response['results']['status']) && $response['results']['status'] == 'success') {
        return [
          'card_type' => $card_type,
          'last4' => substr($payment_details['number'], -4),
          'expiration_month' => $exp_month,
          'expiration_year' => $exp_year,
          'token' => [
            'token_type' => $results['type'],
            'token_data' => $results['token'],
          ],
        ];
      }
      else {
        return [];
      }
    }
    catch (RequestException $e) {
      \Drupal::logger('commerce_payeezy')->error($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    $payment_method->delete();
  }

  /**
   * Payeezy HMAC Authentication.
   *
   * @param string $payload
   *   Payload to create hash string value.
   *
   * @return array
   *   Returns HMAC Authorization token value.
   */
  protected function getHmacAuthorizationToken($payload) {
    $nonce = strval(hexdec(bin2hex(openssl_random_pseudo_bytes(4, $cstrong))));
    $timestamp = strval(time() * 1000);
    $data = $this->configuration['api_key'] . $nonce . $timestamp . $this->configuration['merchant_token'] . $payload;
    $hashAlgorithm = 'sha256';

    // HMAC Hash in hex.
    $hmac = hash_hmac($hashAlgorithm, $data, $this->configuration['api_secret_key'], FALSE);
    $authorization = base64_encode($hmac);

    return [
      'apikey' => strval($this->configuration['api_key']),
      'token' => strval($this->configuration['merchant_token']),
      'Content-type' => 'application/json',
      'Authorization' => $authorization,
      'nonce' => $nonce,
      'timestamp' => $timestamp,
    ];
  }

  /**
   * Perform a Payeezy POST request.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   Payment information.
   * @param array $post_data
   *   Data to be sent to Payeezy.
   * @param string $transaction_url
   *   Transaction URL for Payeezy.
   *
   * @return array|mixed
   *   Returns JSON decoded response from Payeezy.
   */
  protected function payeezyPost(PaymentInterface $payment, array $post_data, $transaction_url) {
    $response = [];

    // Fire a POST request to Payeezy.
    $encoded_data = Json::encode($post_data);
    $headers = $this->getHmacAuthorizationToken($encoded_data);

    try {
      $request = \Drupal::httpClient()->request('POST', $transaction_url, [
        'headers' => $headers,
        'body' => $encoded_data,
      ]);
      $response = $request->getBody()->getContents();
    }
    catch (RequestException $e) {
      \Drupal::logger('commerce_payeezy')->error($e->getMessage());
    }

    if (!empty($response)) {
      return Json::decode($response);
    }
    else {
      return [];
    }
  }

}
