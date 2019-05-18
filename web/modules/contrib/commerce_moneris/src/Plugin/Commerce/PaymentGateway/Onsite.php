<?php

namespace Drupal\commerce_moneris\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidResponseException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Exception\SoftDeclineException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Moneris On-site(API) payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "moneris_onsite",
 *   label = "Moneris Onsite",
 *   display_label = "Moneris",
 *    forms = {
 *     "add-payment-method" = "Drupal\commerce_moneris\PluginForm\Onsite\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class Onsite extends OnsitePaymentGatewayBase implements OnsiteInterface {

  /**
   * The 20 character descriptor sent with transactions.
   *
   * @var string
   */
  protected $dynamicDescriptor;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $site_name = \Drupal::config('system.site')->get('name');
    $this->dynamicDescriptor = substr($site_name, 0, 20);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['store_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Store ID'),
      '#default_value' => $this->configuration['store_id'],
      '#required' => TRUE,
    ];
    $form['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Token'),
      '#default_value' => $this->configuration['api_token'],
      '#required' => TRUE,
    ];
    $form['country_code'] = [
      '#type' => 'select',
      '#options' => ['CA' => 'Canada', 'US' => 'US'],
      '#title' => $this->t('Country'),
      '#default_value' => $this->configuration['country_code'],
      '#required' => TRUE,
    ];
    $form['should_pass_avs_info'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Pass AVS information'),
      '#description' => $this->t('Send AVS information during a transaction. Disabling this option can result in reduced protection against fraud'),
      '#default_value' => isset($this->configuration['should_pass_avs_info']) ? $this->configuration['should_pass_avs_info'] : TRUE,
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
      $this->configuration['store_id'] = $values['store_id'];
      $this->configuration['api_token'] = $values['api_token'];
      $this->configuration['country_code'] = $values['country_code'];
      $this->configuration['should_pass_avs_info'] = $values['should_pass_avs_info'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);

    $amount = $payment->getAmount();
    $number = $amount->getNumber();
    $payment_method_token = $payment_method->getRemoteId();

    $moneris_order_id = $payment->getOrderId() . '-' . $payment->uuid() . '-' . date("dmy-G:i:s");

    $transaction = [
      'type' => $capture ? 'res_purchase_cc' : 'res_preauth_cc',
      'data_key' => $payment_method_token,
      'order_id' => $moneris_order_id,
      'cust_id' => $payment->getOrder()->getCustomerId(),
      'amount' => $number,
      'crypt_type' => 7,
      'dynamic_descriptor' => $this->dynamicDescriptor,
    ];

    $mpgTxn = new \mpgTransaction($transaction);

    $mpgRequest = new \mpgRequest($mpgTxn);
    $mpgRequest->setProcCountryCode($this->configuration['country_code']);
    $test = $this->getMode() == 'test';
    $mpgRequest->setTestMode($test);

    $mpgHttpPost = new \mpgHttpsPost($this->configuration['store_id'], $this->configuration['api_token'], $mpgRequest);

    $mpgResponse = $mpgHttpPost->getMpgResponse();

    $this->checkResponse($mpgResponse);

    $payment_state = $capture ? 'completed' : 'authorization';
    $payment->setState($payment_state);
    $payment->setRemoteId($mpgResponse->getTxnNumber() . '|' . $moneris_order_id);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();
    $number = $amount->getNumber();

    $remote_id = explode('|', $payment->getRemoteId());

    $txn_number = $remote_id[0];
    $moneris_order_id = $remote_id[1];

    $transaction = [
      'type' => 'completion',
      'txn_number' => $txn_number,
      'order_id' => $moneris_order_id,
      'comp_amount' => $number,
      'crypt_type' => '7',
      'cust_id' => $payment->getOrder()->getCustomerId(),
      'dynamic_descriptor' => $this->dynamicDescriptor,
    ];

    $mpgTxn = new \mpgTransaction($transaction);

    $mpgRequest = new \mpgRequest($mpgTxn);
    $mpgRequest->setProcCountryCode($this->configuration['country_code']);
    $test = $this->getMode() == 'test';
    $mpgRequest->setTestMode($test);

    $mpgHttpPost = new \mpgHttpsPost($this->configuration['store_id'], $this->configuration['api_token'], $mpgRequest);

    $mpgResponse = $mpgHttpPost->getMpgResponse();

    $this->checkResponse($mpgResponse);

    $payment->setState('completed');
    $payment->setRemoteId($mpgResponse->getTxnNumber() . '|' . $moneris_order_id);
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);

    $remote_id = explode('|', $payment->getRemoteId());

    $txn_number = $remote_id[0];
    $moneris_order_id = $remote_id[1];

    $transaction = [
      'type' => 'completion',
      'txn_number' => $txn_number,
      'order_id' => $moneris_order_id,
      'comp_amount' => '0.00',
      'crypt_type' => '7',
      'cust_id' => $payment->getOrder()->getCustomerId(),
      'dynamic_descriptor' => $this->dynamicDescriptor,
    ];

    $mpgTxn = new \mpgTransaction($transaction);

    $mpgRequest = new \mpgRequest($mpgTxn);
    $mpgRequest->setProcCountryCode($this->configuration['country_code']);
    $test = $this->getMode() == 'test';
    $mpgRequest->setTestMode($test);

    $mpgHttpPost = new \mpgHttpsPost($this->configuration['store_id'], $this->configuration['api_token'], $mpgRequest);

    $mpgResponse = $mpgHttpPost->getMpgResponse();

    $this->checkResponse($mpgResponse);

    $payment->setState('authorization_voided');
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    // Validate the requested amount.
    $this->assertRefundAmount($payment, $amount);

    $number = $amount->getNumber();

    $remote_id = explode('|', $payment->getRemoteId());

    $txn_number = $remote_id[0];
    $moneris_order_id = $remote_id[1];

    $transaction = [
      'type' => 'refund',
      'txn_number' => $txn_number,
      'order_id' => $moneris_order_id,
      'amount' => $number,
      'crypt_type' => '7',
      'cust_id' => $payment->getOrder()->getCustomerId(),
      'dynamic_descriptor' => $this->dynamicDescriptor,
    ];

    $mpgTxn = new \mpgTransaction($transaction);

    $mpgRequest = new \mpgRequest($mpgTxn);
    $mpgRequest->setProcCountryCode($this->configuration['country_code']);
    $test = $this->getMode() == 'test';
    $mpgRequest->setTestMode($test);

    $mpgHttpPost = new \mpgHttpsPost($this->configuration['store_id'], $this->configuration['api_token'], $mpgRequest);

    $mpgResponse = $mpgHttpPost->getMpgResponse();

    $this->checkResponse($mpgResponse);

    $old_refunded_amount = $payment->getRefundedAmount();
    $new_refunded_amount = $old_refunded_amount->add($amount);
    if ($new_refunded_amount->lessThan($payment->getAmount())) {
      $payment->setState('partially_refunded');
    }
    else {
      $payment->setState('refunded');
    }

    $payment->setRefundedAmount($new_refunded_amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function createPaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $required_keys = [
      // The expected keys are payment gateway specific and usually match
      // the PaymentMethodAddForm form elements. They are expected to be valid.
      'type', 'number', 'expiration',
    ];
    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }

    $owner = $payment_method->getOwner();

    $transaction = [
      'type' => 'res_add_cc',
      'cust_id' => $owner->getAccountName(),
      'email' => $owner->getEmail(),
      'pan' => $payment_details['number'],
      'expdate' => substr($payment_details['expiration']['year'], 2, 2) . $payment_details['expiration']['month'],
      'crypt_type' => 7,
    ];

    /** @var \Drupal\address\AddressInterface $address */
    $address = $payment_method->getBillingProfile()->address->first();

    $mpgTxn = new \mpgTransaction($transaction);

    if ($this->configuration['should_pass_avs_info']) {
      $avs = [
        'avs_street_number' => $address->getAddressLine1(),
        'avs_street_name' => $address->getAddressLine1(),
        'avs_zipcode' => $address->getPostalCode(),
      ];
      $mpgAvsInfo = new \mpgAvsInfo($avs);
      $mpgTxn->setAvsInfo($mpgAvsInfo);
    }

    $mpgRequest = new \mpgRequest($mpgTxn);
    $mpgRequest->setProcCountryCode($this->configuration['country_code']);
    $test = $this->getMode() == 'test';
    $mpgRequest->setTestMode($test);

    $mpgHttpPost = new \mpgHttpsPost($this->configuration['store_id'], $this->configuration['api_token'], $mpgRequest);
    $mpgResponse = $mpgHttpPost->getMpgResponse();

    if (!$mpgResponse->getResSuccess()) {
      throw new InvalidResponseException($mpgResponse->getResponseCode() . ': ' . $mpgResponse->getMessage());
    }

    $payment_method->card_type = $payment_details['type'];
    // Only the last 4 numbers are safe to store.
    $payment_method->card_number = substr($payment_details['number'], -4);
    $payment_method->card_exp_month = $payment_details['expiration']['month'];
    $payment_method->card_exp_year = $payment_details['expiration']['year'];
    $expires = CreditCard::calculateExpirationTimestamp($payment_details['expiration']['month'], $payment_details['expiration']['year']);
    $payment_method->setRemoteId($mpgResponse->getDataKey());
    $payment_method->setExpiresTime($expires);
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    $transaction = [
      'type' => 'res_delete',
      'data_key' => $payment_method->getRemoteId(),
    ];

    $mpgTxn = new \mpgTransaction($transaction);

    $mpgRequest = new \mpgRequest($mpgTxn);
    $mpgRequest->setProcCountryCode($this->configuration['country_code']);
    $test = $this->getMode() == 'test';
    $mpgRequest->setTestMode($test);

    $mpgHttpPost = new \mpgHttpsPost($this->configuration['store_id'], $this->configuration['api_token'], $mpgRequest);

    $mpgResponse = $mpgHttpPost->getMpgResponse();

    if (!$mpgResponse->getResSuccess()) {
      throw new InvalidResponseException($mpgResponse->getResponseCode() . ': ' . $mpgResponse->getMessage());
    }

    $payment_method->delete();
  }

  /**
   * Takes a Moneris response and checks if it returned an error.
   *
   * On a error code it will throw an exception and dump the message.
   *
   * @param \mpgResponse $mpgResponse
   *   A moneris SDK response object.
   *
   * @throws \Drupal\commerce_payment\Exception\PaymentGatewayException
   */
  protected function checkResponse(\mpgResponse $mpgResponse) {
    $soft_decline_codes = ['483', '484', '447'];
    $payment_gateway_codes = ['113'];

    $complete = $mpgResponse->getComplete();
    $timeout = $mpgResponse->getTimedOut();

    $response_code = $mpgResponse->getResponseCode();

    if ($timeout != 'false') {
      throw new PaymentGatewayException('Connection to Moneris timed out');
    }

    if ($response_code == 'null' || $response_code >= 50 || $complete == 'false') {
      if ($response_code != 'null') {
        $error_message = 'Response Code: ' . $response_code . ' - ' . $mpgResponse->getMessage();

        if (in_array($response_code, $soft_decline_codes)) {
          throw new SoftDeclineException($error_message);
        }
        elseif (in_array($response_code, $payment_gateway_codes)) {
          throw new PaymentGatewayException($error_message);
        }
        // All other error codes are hard declines.
        else {
          throw new HardDeclineException($error_message);
        }
      }
      else {
        // If you send invalid data or some sort to Moneris, it will
        // not return an error code and likely a cryptic error message.
        throw new InvalidResponseException($mpgResponse->getMessage());
      }

      throw new PaymentGatewayException('Unknown payment gateway error, error message not available');
    }
  }

}
