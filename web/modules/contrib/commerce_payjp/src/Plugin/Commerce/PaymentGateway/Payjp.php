<?php

namespace Drupal\commerce_payjp\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payjp\ErrorHelper;
use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Datetime\TimeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the Pay.JP payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "payjp",
 *   label = "Pay.JP (CreditCard)",
 *   display_label = "Pay.JP",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_payjp\PluginForm\Payjp\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "mastercard", "visa",
 *   },
 *   js_library = "commerce_payjp/form",
 * )
 */
class Payjp extends OnsitePaymentGatewayBase implements PayjpInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    \Payjp\Payjp::setApiKey($this->configuration['secret_key']);
  }

  /**
   * Sets the API key after the plugin is unserialized.
   */
  public function __wakeup() {
    \Payjp\Payjp::setApiKey($this->configuration['secret_key']);
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
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);

    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $test_card = [
        "number" => "4242424242424242",
        "cvc" => "123",
        "exp_month" => "2",
        "exp_year" => "2099",
      ];
      // Validate for enable mode only. Live mode must be approved by Pay.JP.
      if (!empty($values['secret_key']) && !empty($values['public_key'])) {
        if ($values['mode'] == 'test') {
          try {
            \Payjp\Payjp::setApiKey($values['secret_key']);
            // Make sure we use the right mode for the secret keys.
            if (\Payjp\Account::retrieve()->offsetGet('livemode_enabled')) {
              $form_state->setError($form['secret_key'], $this->t('The Secret key is not for test'));
            }
          }
          catch (\Payjp\Error\Base $e) {
            $form_state->setError($form['secret_key'], $this->t('Invalid Secret key'));
          }
          try {
            \Payjp\Payjp::setApiKey($values['public_key']);
            // Make sure we use the right mode for the secret keys.
            if (\Payjp\Token::create(['card' => $test_card])
              ->offsetGet('livemode')
            ) {
              $form_state->setError($form['public_key'], $this->t('The Public key is not for test'));
            }
          }
          catch (\Payjp\Error\Base $e) {
            $form_state->setError($form['public_key'], $this->t('Invalid Public key'));
          }
        }
        else {
          try {
            \Payjp\Payjp::setApiKey($values['secret_key']);
            // Make sure we use the right mode for the secret keys.
            $merchant = \Payjp\Account::retrieve()->__toArray();
            if (!$merchant['merchant']->offsetGet("livemode_enabled")) {
              $form_state->setError($form['secret_key'], $this->t('The Secret key is not for live'));
            }
          }
          catch (\Payjp\Error\Base $e) {
            $form_state->setError($form['secret_key'], $this->t('Invalid Secret key or Secret key is not enabled by Pay.JP'));
          }
          // @todo Live mode does not allow using a test card.
        }
      }
    }
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
      'amount' => $this->formatNumber($amount->getNumber()),
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
      $result = \Payjp\Charge::create($transaction_data);
    }
    catch (\Payjp\Error\Base $e) {
      ErrorHelper::handleException($e);
    }

    $next_state = $capture ? 'completed' : 'authorization';
    $payment->setState($next_state);
    $payment->setRemoteId($result['id']);
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
      $charge = \Payjp\Charge::retrieve($remote_id);
      $charge->capture();
    }
    catch (\Payjp\Error\Base $e) {
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

    // Void Payjp payment - release uncaptured payment.
    try {
      $remote_id = $payment->getRemoteId();
      $charge = \Payjp\Charge::retrieve($remote_id);
      $charge->refund();
    }
    catch (\Payjp\Error\Base $e) {
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
        'amount' => $this->formatNumber(($amount->getNumber())),
      ];
      $charge = \Payjp\Charge::retrieve($remote_id);
      $charge->refund($data);
    }
    catch (\Payjp\Error\Base $e) {
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
      'payjp_token',
    ];
    foreach ($required_keys as $required_key) {
      if (empty($payment_details[$required_key])) {
        throw new \InvalidArgumentException(sprintf('$payment_details must contain the %s key.', $required_key));
      }
    }

    $remote_payment_method = $this->doCreatePaymentMethod($payment_method, $payment_details);
    $payment_method->card_type = $this->mapCreditCardType($remote_payment_method['brand']);
    $payment_method->card_number = $remote_payment_method['last4'];
    $payment_method->card_exp_month = $remote_payment_method['exp_month'];
    $payment_method->card_exp_year = $remote_payment_method['exp_year'];
    $remote_id = $remote_payment_method['id'];
    $expires = CreditCard::calculateExpirationTimestamp($remote_payment_method['exp_month'], $remote_payment_method['exp_year']);
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
        $customer_id = $this->getRemoteCustomerId($owner);
        $customer = \Payjp\Customer::retrieve($customer_id);
        $customer->cards->retrieve($payment_method->getRemoteId())->delete();
      }
    }
    catch (\Payjp\Error\Base $e) {
      ErrorHelper::handleException($e);
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
      // use the Payjp form token to create the new card.
      $customer = \Payjp\Customer::retrieve($customer_id);
      // Create a payment method for an existing customer.
      $card = $customer->cards->create(['card' => $payment_details['payjp_token']]);
      return $card;
    }
    elseif ($owner && $owner->isAuthenticated()) {
      // Create both the customer and the payment method.
      try {
        $customer = \Payjp\Customer::create([
          'email' => $owner->getEmail(),
          'description' => $this->t('Customer for :mail', [':mail' => $owner->getEmail()]),
          'card' => $payment_details['payjp_token'],
        ]);
        $cards = \Payjp\Customer::retrieve($customer->id)->cards->all();
        $cards_array = \Payjp\Util\Util::convertPayjpObjectToArray([$cards]);
        $this->setRemoteCustomerId($owner, $customer->id);
        $owner->save();
        foreach ($cards_array[0]['data'] as $card) {
          return $card;
        }
      }
      catch (\Payjp\Error\Base $e) {
        ErrorHelper::handleException($e);
      }
    }
    else {
      $card_token = \Payjp\Token::retrieve($payment_details['payjp_token']);
      // Anonymous customers.
      $card_token->card['id'] = $payment_details['payjp_token'];
      return $card_token->card;
    }

    return [];
  }

  /**
   * Maps the Payjp credit card type to a Commerce credit card type.
   *
   * @param string $card_type
   *   The Payjp credit card type.
   *
   * @return string
   *   The Commerce credit card type.
   */
  protected function mapCreditCardType($card_type) {
    $map = [
      'American Express' => 'amex',
      'Diners Club' => 'dinersclub',
      'Discover' => 'discover',
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
   * Formats the charge amount for Pay.JP.
   *
   * @param int $amount
   *   The amount being charged.
   *
   * @return int
   *   The Pay.JP formatted amount.
   */
  protected function formatNumber($amount) {
    $amount = number_format($amount, 0, '.', '');
    return $amount;
  }

}
