<?php

namespace Drupal\commerce_omise\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_omise\ErrorHelper;
use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;

use OmiseException;
use OmiseCharge;
use OmiseCustomer;
use OmiseToken;

/**
 * Provides the Omise payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "omise",
 *   label = "Omise (CreditCard)",
 *   display_label = "Omise",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_omise\PluginForm\Omise\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "jcb", "mastercard", "visa",
 *   },
 *   js_library = "commerce_omise/form",
 * )
 */
class Omise extends OnsitePaymentGatewayBase implements OmiseInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    // Define configuration constant for Omise PHP library.
    if (!defined('OMISE_PUBLIC_KEY')) {
      define('OMISE_PUBLIC_KEY', $this->configuration['public_key']);
    }
    if (!defined('OMISE_SECRET_KEY')) {
      define('OMISE_SECRET_KEY', $this->configuration['secret_key']);
    }
  }

  /**
   * Sets the API key after the plugin is unserialized.
   */
  public function __wakeup() {
    // Define configuration constant for Omise PHP library.
    if (!defined('OMISE_PUBLIC_KEY')) {
      define('OMISE_PUBLIC_KEY', $this->configuration['public_key']);
    }
    if (!defined('OMISE_SECRET_KEY')) {
      define('OMISE_SECRET_KEY', $this->configuration['secret_key']);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPublicKey() {
    return $this->configuration['public_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'secret_key' => '',
      'public_key' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret key'),
      '#default_value' => $this->configuration['secret_key'],
      '#required' => TRUE,
    ];

    $form['public_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public key'),
      '#default_value' => $this->configuration['public_key'],
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
      $this->configuration['secret_key'] = $values['secret_key'];
      $this->configuration['public_key'] = $values['public_key'];
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
    $currency_code = $payment->getAmount()->getCurrencyCode();

    $transaction_data = [
      'currency' => $currency_code,
      'amount' => $this->formatNumber($amount),
      'capture' => $capture,
    ];

    $owner = $payment_method->getOwner();
    if ($owner && $owner->isAuthenticated()) {
      $transaction_data['customer'] = $this->getRemoteCustomerId($owner);
    }
    else {
      $transaction_data['card'] = $payment_method->getRemoteId();
    }

    try {
      $result = OmiseCharge::create($transaction_data);
    }
    catch (OmiseException $e) {
      ErrorHelper::handleException($e);
    }

    $next_state = $capture ? 'completed' : 'authorization';
    $payment->setState($next_state);
    $payment->setRemoteId($result['id']);
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
      $charge = OmiseCharge::retrieve($remote_id);
      $charge->capture();
    }
    catch (OmiseException $e) {
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
    $amount = $payment->getAmount();
    // Void Omise payment - release uncaptured payment.
    try {
      $remote_id = $payment->getRemoteId();
      $charge = OmiseCharge::retrieve($remote_id);
      $data = [
        'amount' => $this->formatNumber($amount),
        'void' => TRUE,
      ];

      $charge->refunds()->create($data);
    }
    catch (OmiseException $e) {
      ErrorHelper::handleException($e);
    }

    // Update payment.
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

    try {
      $remote_id = $payment->getRemoteId();
      $data = [
        'amount' => $this->formatNumber($amount),
      ];
      $charge = OmiseCharge::retrieve($remote_id);
      $charge->refunds()->create($data);
    }
    catch (OmiseException $e) {
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
    $required_keys = [
      // The expected keys are payment gateway specific and usually match
      // the PaymentMethodAddForm form elements. They are expected to be valid.
      'omise_token',
    ];
    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }

    $remote_payment_method = $this->doCreatePaymentMethod($payment_method, $payment_details);
    $payment_method->card_type = $this->mapCreditCardType($remote_payment_method['brand']);
    $payment_method->card_number = $remote_payment_method['last_digits'];
    $payment_method->card_exp_month = $remote_payment_method['expiration_month'];
    $payment_method->card_exp_year = $remote_payment_method['expiration_year'];
    $remote_id = $remote_payment_method['id'];
    $expires = CreditCard::calculateExpirationTimestamp($remote_payment_method['expiration_month'], $remote_payment_method['expiration_year']);
    $payment_method->setRemoteId($remote_id);
    $payment_method->setExpiresTime($expires);
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // Delete the remote record.
    try {
      $owner = $payment_method->getOwner();
      if ($owner) {
        $customer_id = $owner->commerce_remote_id->getByProvider('commerce_omise');
        $customer = OmiseCustomer::retrieve($customer_id);
        $customer->cards()->retrieve($payment_method->getRemoteId())->destroy();
      }
    }
    catch (OmiseException $e) {
      ErrorHelper::handleException($e);
    }

    $payment_method->delete();
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
   */
  protected function doCreatePaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {

    $owner = $payment_method->getOwner();
    $customer_id = NULL;
    if ($owner && $owner->isAuthenticated()) {
      $customer_id = $this->getRemoteCustomerId($owner);
    }
    if ($customer_id) {
      // If the customer id already exists
      // use the Omise form token to create the new card.
      $customer = OmiseCustomer::retrieve($customer_id);
      // Create a payment method for an existing customer.
      $card = $customer->update(['card' => $payment_details['omise_token']]);
      return $card;
    }
    elseif ($owner && $owner->isAuthenticated()) {
      // Create both the customer and the payment method.
      try {
        $customer = OmiseCustomer::create([
          'email' => $owner->getEmail(),
          'description' => $this->t('Customer for :mail', [':mail' => $owner->getEmail()]),
          'card' => $payment_details['omise_token'],
        ]);

        $cards = OmiseCustomer::retrieve($customer['id'])->cards();

        $this->setRemoteCustomerId($owner, $customer['id']);
        $owner->save();
        foreach ($cards['data'] as $card) {
          return $card;
        }
      }
      catch (OmiseException $e) {
        ErrorHelper::handleException($e);
      }
    }
    else {
      $card_token = OmiseToken::retrieve($payment_details['omise_token']);
      $card = $card_token['card'];
      $card['id'] = $payment_details['omise_token'];
      return $card;
    }

    return [];
  }

  /**
   * Maps the Omise credit card type to a Commerce credit card type.
   *
   * @param string $card_type
   *   The Omise credit card type.
   *
   * @return string
   *   The Commerce credit card type.
   */
  protected function mapCreditCardType($card_type) {
    $map = [
      'American Express' => 'amex',
      'Diners Club' => 'dinersclub',
      'JCB' => 'jcb',
      'MasterCard' => 'mastercard',
      'Visa' => 'visa',
    ];
    if (!isset($map[$card_type])) {
      throw new HardDeclineException(sprintf('Unsupported credit card type "%s".', $card_type));
    }

    return $map[$card_type];
  }

  /**
   * Formats the charge amount for Omise.
   *
   * @param \Drupal\commerce_price\Price $amount
   *   The amount being charged.
   *
   * @return int
   *   The Omise formatted amount.
   */
  protected function formatNumber(Price $amount) {
    $amount_number = $amount->getNumber();

    switch ($amount->getCurrencyCode()) {
      case 'THB':
      case 'SGD':
      case 'USD':
      case 'EUR':
      case 'GBP':
        $amount_number = $amount_number * 100;
        break;

      default:
        break;
    }

    $amount_number = number_format($amount_number, 0, '.', '');

    return $amount_number;
  }

}
