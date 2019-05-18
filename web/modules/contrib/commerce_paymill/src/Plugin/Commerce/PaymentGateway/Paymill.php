<?php

namespace Drupal\commerce_paymill\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the Paymill payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "paymill",
 *   label = @Translation("Paymill"),
 *   display_label = @Translation("Paymill"),
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_paymill\PluginForm\Paymill\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 *   js_library = "commerce_paymill/form",
 * )
 */
class Paymill extends OnsitePaymentGatewayBase implements PaymillInterface {

  /**
   * The Paymill gateway used for making API calls.
   *
   * @var \Paymill\Request
   */
  protected $api;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->api = new \Paymill\Request($this->configuration['private_key']);
    $this->public_key = $this->getPaymillPublicKey();
  }

  /**
   * {@inheritdoc}
   */
  public function getPaymillPublicKey() {
    return $key = $this->configuration['public_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'private_key' => '',
      'public_key' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private key'),
      '#default_value' => $this->configuration['private_key'],
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
      $this->configuration['private_key'] = $values['private_key'];
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
    $customer_id = NULL;
    $owner = $payment_method->getOwner();
    if ($owner && !$owner->isAnonymous()) {
      $customer_id = $owner->commerce_remote_id->getByProvider('commerce_paymill');
    }

    // Create Paymill payment or preauthorization.
    try {
      if ($capture) {
        // Create Paymill transaction.
        $paymill_transaction = new \Paymill\Models\Request\Transaction();
        $paymill_transaction->setAmount($this->amountGetInteger($amount))
          ->setCurrency($currency_code)
          ->setPayment($payment_method->getRemoteId());
        if ($customer_id) {
          $paymill_transaction->setClient($customer_id);
        }

        $remote_transaction = $this->api->create($paymill_transaction);
        $payment->setRemoteId($remote_transaction->getId());
      }
      else {
        // Create Paymill preauthorization.
        $paymill_preauthorization = new \Paymill\Models\Request\Preauthorization();
        $paymill_preauthorization->setPayment($payment_method->getRemoteId())
          ->setAmount($this->amountGetInteger($amount))
          ->setCurrency($currency_code);
        if ($customer_id) {
          $paymill_preauthorization->setClient($customer_id);
        }
        $remote_preauthorization = $this->api->create($paymill_preauthorization);
        $payment->setRemoteId($remote_preauthorization->getId());
      }
    }
    catch (\Paymill\Services\PaymillException $e) {
      throw new PaymentGatewayException($e->getErrorMessage(), $e->getResponseCode());
    }

    $payment->state = $capture ? 'completed' : 'authorization';
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function capturePayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['authorization']);
    // If not specified, capture the entire amount.
    $amount = $amount ?: $payment->getAmount();

    // Capture Paymill transaction.
    try {
      $paymill_transaction = new \Paymill\Models\Request\Transaction();
      $paymill_transaction->setAmount($this->amountGetInteger($amount))
        ->setCurrency($amount->getCurrencyCode())
        ->setPreauthorization($payment->getRemoteId());
      $remote_transaction = $this->api->create($paymill_transaction);
    }
    catch (\Paymill\Services\PaymillException $e) {
      throw new PaymentGatewayException($e->getErrorMessage(), $e->getResponseCode());
    }

    $payment->setState('completed');
    $payment->setRemoteId($remote_transaction->getId());
    $payment->setAmount($amount);
    $payment->save();
  }

  /**
   * {@inheritdoc}
   */
  public function voidPayment(PaymentInterface $payment) {
    $this->assertPaymentState($payment, ['authorization']);

    // Void Paymill transaction - delete the preauthorization.
    try {
      $paymill_preauthorization = new \Paymill\Models\Request\Preauthorization();
      $paymill_preauthorization->setId($payment->getRemoteId());
      /** @var \Paymill\Models\Response\Preauthorization $remote_preauthorization */
      $remote_preauthorization = $this->api->getOne($paymill_preauthorization);
      $this->api->delete($paymill_preauthorization);
    }
    catch (\Paymill\Services\PaymillException $e) {
      throw new PaymentGatewayException($e->getErrorMessage(), $e->getResponseCode());
    }

    $payment->setState('authorization_voided');
    // Update the remote id with transaction id of the deleted preauthorization.
    $payment->setRemoteId($remote_preauthorization->getTransaction()->getId());
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
      $paymill_refund = new \Paymill\Models\Request\Refund();
      $paymill_refund->setId($payment->getRemoteId())
        ->setAmount($this->amountGetInteger($amount))
        ->setDescription('Sample Description');
      $this->api->create($paymill_refund);
    }
    catch (\Paymill\Services\PaymillException $e) {
      throw new PaymentGatewayException($e->getErrorMessage(), $e->getResponseCode());
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
      'paymill_token'
    ];
    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }

    /** @var \Paymill\Models\Response\Payment $remote_payment_method */
    try {
      $remote_payment_method = $this->doCreatePaymentMethod($payment_method, $payment_details);
    }
    catch (\Paymill\Services\PaymillException $e) {
      throw new PaymentGatewayException($e->getErrorMessage(), $e->getResponseCode());
    }
    $payment_method->card_type = $this->mapCreditCardType($remote_payment_method->getCardType());
    $payment_method->card_number = $remote_payment_method->getLastFour();
    $payment_method->card_exp_month = $remote_payment_method->getExpireMonth();
    $payment_method->card_exp_year = $remote_payment_method->getExpireYear();
    $remote_id = $remote_payment_method->getId();
    $expires = CreditCard::calculateExpirationTimestamp($remote_payment_method->getExpireMonth(), $remote_payment_method->getExpireYear());
    $payment_method->setRemoteId($remote_id);
    $payment_method->setExpiresTime($expires);
    $payment_method->save();
  }

  /**
   * {@inheritdoc}
   */
  public function deletePaymentMethod(PaymentMethodInterface $payment_method) {
    // Delete the remote Paymill payment.
    try {
      $paymill_payment = new \Paymill\Models\Request\Payment();
      $paymill_payment->setId($payment_method->getRemoteId());
      $this->api->delete($paymill_payment);
    }
    catch (\Paymill\Services\PaymillException $e) {
      throw new PaymentGatewayException($e->getErrorMessage(), $e->getResponseCode());
    }
    // Delete the local entity.
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
   * @return \Paymill\Models\Response\Payment $remote_payment_method
   *   The Paymill API payment object.
   */
  protected function doCreatePaymentMethod(PaymentMethodInterface $payment_method, array $payment_details) {
    $owner = $payment_method->getOwner();
    $customer_id = NULL;
    $create_client = FALSE;
    if ($owner && !$owner->isAnonymous()) {
      $customer_id = $this->getRemoteCustomerId($owner);
      if (!$customer_id) {
        $create_client = TRUE;
      }
      $customer_email = $owner->getEmail();
    }

    $client = new \Paymill\Models\Request\Client();

    // Check if the customer exists as Paymill client, if not create a new one.
    if ($customer_id) {
      $client->setId($customer_id);
      /** @var \Paymill\Models\Response\Client $remote_client */
      $remote_client = $this->api->getOne($client);

      if (!empty($remote_client->getId())) {
        $create_client = FALSE;
      }
    }

    // Create Paymill client if there is no client found or already set.
    if ($create_client) {
      $client->setEmail($owner->getEmail())
        ->setDescription(t('Customer for :mail', array(':mail' => $customer_email)));
      $remote_client = $this->api->create($client);
      if (!empty($remote_client->getId())) {
        $this->setRemoteCustomerId($owner, $remote_client->getId());
        $owner->save();
      }
    }

    // Create new Paymill payment.
    $paymill_payment = new \Paymill\Models\Request\Payment();
    $paymill_payment->setToken($payment_details['paymill_token']);
    // Create a payment method for an existing customer.
    if ($customer_id) {
      $paymill_payment->setClient($customer_id);
    }
    $remote_payment_method = $this->api->create($paymill_payment);
    return $remote_payment_method;
  }

  /**
   * Maps the Paymill credit card type to a Commerce credit card type.
   *
   * @param string $card_type
   *   The Paymill credit card type.
   *
   * @return string
   *   The Commerce credit card type.
   */
  protected function mapCreditCardType($card_type) {
    // Card types supported by Paymill.
    // https://developers.paymill.com/API/index#list-payments-
    $map = [
      'amex' => 'amex',
      'diners' => 'dinersclub',
      'discover' => 'discover',
      'jcb' => 'jcb',
      'maestro' => 'maestro',
      'mastercard' => 'mastercard',
      'visa' => 'visa',
    ];
    if (!isset($map[$card_type])) {
      throw new HardDeclineException(sprintf('Unsupported credit card type "%s".', $card_type));
    }

    return $map[$card_type];
  }

  /**
   * {@inheritdoc}
   */
  public function amountGetInteger(Price $amount) {
    $amount_number = $amount->getNumber();
    $amount_number = $amount_number * 100;
    $amount_integer = number_format($amount_number, 0, '.', '');

    return $amount_integer;
  }

}
