<?php

namespace Drupal\commerce_braintree\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_braintree\ErrorHelper;
use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the HostedFields payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "braintree_hostedfields",
 *   label = "Braintree (Hosted Fields)",
 *   display_label = "Braintree",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_braintree\PluginForm\HostedFields\PaymentMethodAddForm",
 *   },
 *   js_library = "commerce_braintree/braintree",
 *   payment_method_types = {"credit_card", "paypal", "paypal_credit"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 * )
 */
class HostedFields extends OnsitePaymentGatewayBase implements HostedFieldsInterface {

  /**
   * The Braintree gateway used for making API calls.
   *
   * @var \Braintree\Gateway
   */
  protected $api;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->api = new \Braintree\Gateway([
      'environment' => ($this->getMode() == 'test') ? 'sandbox' : 'production',
      'merchantId' => $this->configuration['merchant_id'],
      'publicKey' => $this->configuration['public_key'],
      'privateKey' => $this->configuration['private_key'],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'merchant_id' => '',
      'public_key' => '',
      'private_key' => '',
      'merchant_account_id' => [],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant ID'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];
    $form['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public key'),
      '#default_value' => $this->configuration['public_key'],
      '#required' => TRUE,
    ];
    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private key'),
      '#default_value' => $this->configuration['private_key'],
      '#required' => TRUE,
    ];
    // Braintree supports multiple currencies through the use of multiple
    // merchant accounts.
    $form['merchant_account_id'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Merchant account ID'),
      '#description' => $this->t('To find your Merchant account ID: log into the Braintree Control Panel; navigate to Settings > Processing > Merchant Accounts. <a href="@url" target="_blank">Read more</a>.',
        ['@url' => 'https://articles.braintreepayments.com/control-panel/important-gateway-credentials#merchant-account-id']),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    ];
    $currency_storage = $this->entityTypeManager->getStorage('commerce_currency');
    foreach ($currency_storage->loadMultiple() as $currency_id => $currency) {
      $merchant_account_id = NULL;
      if (isset($this->configuration['merchant_account_id'][$currency_id])) {
        $merchant_account_id = $this->configuration['merchant_account_id'][$currency_id];
      }

      $form['merchant_account_id'][$currency_id] = [
        '#type' => 'textfield',
        '#title' => $this->t('Merchant account ID for @currency', ['@currency' => $currency->label()]),
        '#size' => 30,
        '#maxlength' => 128,
        '#default_value' => $merchant_account_id,
        '#required' => TRUE,
      ];
    }

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
      $this->configuration['public_key'] = $values['public_key'];
      $this->configuration['private_key'] = $values['private_key'];
      $this->configuration['merchant_account_id'] = $values['merchant_account_id'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function generateClientToken() {
    return $this->api->clientToken()->generate();
  }

  /**
   * {@inheritdoc}
   */
  public function createPayment(PaymentInterface $payment, $capture = TRUE) {
    $this->assertPaymentState($payment, ['new']);
    $payment_method = $payment->getPaymentMethod();
    $this->assertPaymentMethod($payment_method);
    $amount = $payment->getAmount();
    $currency_code = $payment->getAmount()->getCurrencyCode();
    if (empty($this->configuration['merchant_account_id'][$currency_code])) {
      throw new InvalidRequestException(sprintf('No merchant account ID configured for currency %s', $currency_code));
    }

    $transaction_data = [
      'channel' => 'CommerceGuys_BT_Vzero',
      'merchantAccountId' => $this->configuration['merchant_account_id'][$currency_code],
      // orderId must be unique.
      'orderId' => $payment->getOrderId() . '-' . $this->time->getCurrentTime(),
      'amount' => $amount->getNumber(),
      'options' => [
        'submitForSettlement' => $capture,
      ],
    ];
    if ($payment_method->isReusable()) {
      $transaction_data['paymentMethodToken'] = $payment_method->getRemoteId();
    }
    else {
      $transaction_data['paymentMethodNonce'] = $payment_method->getRemoteId();
    }

    try {
      $result = $this->api->transaction()->sale($transaction_data);
      ErrorHelper::handleErrors($result);
    }
    catch (\Braintree\Exception $e) {
      ErrorHelper::handleException($e);
    }

    $next_state = $capture ? 'completed' : 'authorization';
    $payment->setState($next_state);
    $payment->setRemoteId($result->transaction->id);
    // @todo Find out how long an authorization is valid, set its expiration.
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

    try {
      $remote_id = $payment->getRemoteId();
      $decimal_amount = $amount->getNumber();
      $result = $this->api->transaction()->submitForSettlement($remote_id, $decimal_amount);
      ErrorHelper::handleErrors($result);
    }
    catch (\Braintree\Exception $e) {
      ErrorHelper::handleException($e);
    }

    $payment->setState('completed');
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);

    try {
      $remote_id = $payment->getRemoteId();
      $result = $this->api->transaction()->void($remote_id);
      ErrorHelper::handleErrors($result);
    }
    catch (\Braintree\Exception $e) {
      ErrorHelper::handleException($e);
    }

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
    $this->assertRefundAmount($payment, $amount);

    try {
      $remote_id = $payment->getRemoteId();
      $decimal_amount = $amount->getNumber();
      $result = $this->api->transaction()->refund($remote_id, $decimal_amount);
      ErrorHelper::handleErrors($result);
    }
    catch (\Braintree\Exception $e) {
      ErrorHelper::handleException($e);
    }

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
    $payment_method_type = $payment_method->getType()->getPluginId();
    $required_keys = [
      'payment_method_nonce',
    ];

    $paypal_payment_method_types = [
      'paypal',
      'paypal_credit',
    ];
    if (!in_array($payment_method_type, $paypal_payment_method_types)) {
      $required_keys += [
        'card_type', 'last2',
      ];
    }

    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }

    if (!$payment_method->isReusable()) {
      $payment_method->card_type = $this->mapCreditCardType($payment_details['card_type']);
      $payment_method->card_number = $payment_details['last2'];

      $remote_id = $payment_details['payment_method_nonce'];
      // Nonces expire after 3h. We reduce that time by 5s to account for the
      // time it took to do the server request after the JS tokenization.
      $expires = $this->time->getRequestTime() + (3600 * 3) - 5;
    }
    else {
      $remote_payment_method = $this->doCreatePaymentMethod($payment_method, $payment_details);
      $remote_id = $remote_payment_method['token'];

      if (in_array($payment_method_type, $paypal_payment_method_types)) {
        $payment_method->paypal_mail = $remote_payment_method['email'];
        $expires = 0;
      }
      else {
        $payment_method->card_type = $this->mapCreditCardType($remote_payment_method['card_type']);
        $payment_method->card_number = $remote_payment_method['last4'];
        $payment_method->card_exp_month = $remote_payment_method['expiration_month'];
        $payment_method->card_exp_year = $remote_payment_method['expiration_year'];

        $expires = CreditCard::calculateExpirationTimestamp($remote_payment_method['expiration_month'], $remote_payment_method['expiration_year']);
      }
    }

    $payment_method->setRemoteId($remote_id);
    $payment_method->setExpiresTime($expires);
    $payment_method->save();
  }

  /**
   * Creates the payment method on the gateway.
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
   *   PayPal specific keys:
   *   - email: The PayPal email address.
   */
  protected function doCreatePaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $payment_method_type = $payment_method->getType()->getPluginId();

    $owner = $payment_method->getOwner();
    /** @var \Drupal\address\AddressInterface $address */
    $address = $payment_method->getBillingProfile()->address->first();
    // If the owner is anonymous, the created customer will be blank.
    // https://developers.braintreepayments.com/reference/request/customer/create/php#blank-customer
    $customer_id = NULL;
    $customer_data = [];
    if ($owner && $owner->isAuthenticated()) {
      $customer_id = $this->getRemoteCustomerId($owner);
      $customer_data['email'] = $owner->getEmail();
    }
    $billing_address_data = [
      'billingAddress' => [
        'firstName' => $address->getGivenName(),
        'lastName' => $address->getFamilyName(),
        'company' => $address->getOrganization(),
        'streetAddress' => $address->getAddressLine1(),
        'extendedAddress' => $address->getAddressLine2(),
        'locality' => $address->getLocality(),
        'region' => $address->getAdministrativeArea(),
        'postalCode' => $address->getPostalCode(),
        'countryCodeAlpha2' => $address->getCountryCode(),
      ],
    ];
    $payment_method_data = [
      'cardholderName' => $address->getGivenName() . ' ' . $address->getFamilyName(),
      'paymentMethodNonce' => $payment_details['payment_method_nonce'],
      'options' => [
        'verifyCard' => TRUE,
      ],
    ];

    if ($customer_id) {
      // Create a payment method for an existing customer.
      try {
        $data = $billing_address_data + $payment_method_data + [
          'customerId' => $customer_id,
        ];
        $result = $this->api->paymentMethod()->create($data);
        ErrorHelper::handleErrors($result);
      }
      catch (\Braintree\Exception $e) {
        ErrorHelper::handleException($e);
      }

      $remote_payment_method = $result->paymentMethod;
    }
    else {
      // Create both the customer and the payment method.
      try {
        $data = $customer_data + [
          'creditCard' => $billing_address_data + $payment_method_data,
        ];
        $result = $this->api->customer()->create($data);
        ErrorHelper::handleErrors($result);
      }
      catch (\Braintree\Exception $e) {
        ErrorHelper::handleException($e);
      }
      $remote_payment_method = $result->customer->paymentMethods[0];

      if ($owner && $owner->isAuthenticated()) {
        $this->setRemoteCustomerId($owner, $result->customer->id);
        $owner->save();
      }
    }

    if (in_array($payment_method_type, ['paypal', 'paypal_credit'])) {
      return [
        'token' => $remote_payment_method->token,
        'email' => $remote_payment_method->email,
      ];
    }
    else {
      return [
        'token' => $remote_payment_method->token,
        'card_type' => $remote_payment_method->cardType,
        'last4' => $remote_payment_method->last4,
        'expiration_month' => $remote_payment_method->expirationMonth,
        'expiration_year' => $remote_payment_method->expirationYear,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // Delete the remote record.
    try {
      $result = $this->api->paymentMethod()->delete($payment_method->getRemoteId());
      ErrorHelper::handleErrors($result);
    }
    catch (\Braintree\Exception $e) {
      ErrorHelper::handleException($e);
    }
    // Delete the local entity.
    $payment_method->delete();
  }

  /**
   * Maps the Braintree credit card type to a Commerce credit card type.
   *
   * @param string $card_type
   *   The Braintree credit card type.
   *
   * @return string
   *   The Commerce credit card type.
   */
  protected function mapCreditCardType($card_type) {
    $map = [
      'American Express' => 'amex',
      'China UnionPay' => 'unionpay',
      'Diners Club' => 'dinersclub',
      'Discover' => 'discover',
      'JCB' => 'jcb',
      'Maestro' => 'maestro',
      'MasterCard' => 'mastercard',
      'Visa' => 'visa',
    ];
    if (!isset($map[$card_type])) {
      throw new HardDeclineException(sprintf('Unsupported credit card type "%s".', $card_type));
    }

    return $map[$card_type];
  }

}
