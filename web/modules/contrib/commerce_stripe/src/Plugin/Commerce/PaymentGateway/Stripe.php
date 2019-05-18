<?php

namespace Drupal\commerce_stripe\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_stripe\ErrorHelper;
use Drupal\commerce_payment\CreditCard;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\PaymentMethodTypeManager;
use Drupal\commerce_payment\PaymentTypeManager;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\commerce_stripe\Event\TransactionDataEvent;
use Drupal\commerce_stripe\Event\StripeEvents;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Site\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Provides the Stripe payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "stripe",
 *   label = "Stripe",
 *   display_label = "Stripe",
 *   forms = {
 *     "add-payment-method" = "Drupal\commerce_stripe\PluginForm\Stripe\PaymentMethodAddForm",
 *   },
 *   payment_method_types = {"credit_card"},
 *   credit_card_types = {
 *     "amex", "dinersclub", "discover", "jcb", "maestro", "mastercard", "visa",
 *   },
 *   js_library = "commerce_stripe/form",
 * )
 */
class Stripe extends OnsitePaymentGatewayBase implements StripeInterface {

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('plugin.manager.commerce_payment_type'),
      $container->get('plugin.manager.commerce_payment_method_type'),
      $container->get('datetime.time'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManagerInterface $entity_type_manager, PaymentTypeManager $payment_type_manager, PaymentMethodTypeManager $payment_method_type_manager, TimeInterface $time, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $entity_type_manager, $payment_type_manager, $payment_method_type_manager, $time);

    $this->eventDispatcher = $event_dispatcher;
    $this->init();
  }

  /**
   * Re-initializes the SDK after the plugin is unserialized.
   */
  public function __wakeup() {
    $this->init();
  }

  /**
   * Initializes the SDK.
   */
  protected function init() {
    // If Drupal is configured to use a proxy for outgoing requests, make sure
    // that the proxy CURLOPT_PROXY setting is passed to the Stripe SDK client.
    $http_client_config = Settings::get('http_client_config');
    if (!empty($http_client_config['proxy']['https'])) {
      $curl = new \Stripe\HttpClient\CurlClient([CURLOPT_PROXY => $http_client_config['proxy']['https']]);
      \Stripe\ApiRequestor::setHttpClient($curl);
    }

    \Stripe\Stripe::setApiKey($this->configuration['secret_key']);
  }

  /**
   * {@inheritdoc}
   */
  public function getPublishableKey() {
    return $this->configuration['publishable_key'];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'publishable_key' => '',
      'secret_key' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['publishable_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Publishable Key'),
      '#default_value' => $this->configuration['publishable_key'],
      '#required' => TRUE,
    ];
    $form['secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Secret Key'),
      '#default_value' => $this->configuration['secret_key'],
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
      // Validate the secret key.
      $expected_livemode = $values['mode'] == 'live' ? TRUE : FALSE;
      if (!empty($values['secret_key'])) {
        try {
          \Stripe\Stripe::setApiKey($values['secret_key']);
          // Make sure we use the right mode for the secret keys.
          if (\Stripe\Balance::retrieve()->offsetGet('livemode') != $expected_livemode) {
            $form_state->setError($form['secret_key'], $this->t('The provided secret key is not for the selected mode (@mode).', ['@mode' => $values['mode']]));
          }
        }
        catch (\Stripe\Error\Base $e) {
          $form_state->setError($form['secret_key'], $this->t('Invalid secret key.'));
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
      $this->configuration['publishable_key'] = $values['publishable_key'];
      $this->configuration['secret_key'] = $values['secret_key'];
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
    $transaction_data = [
      'currency' => $amount->getCurrencyCode(),
      'amount' => $this->toMinorUnits($amount),
      'source' => $payment_method->getRemoteId(),
      'capture' => $capture,
      'metadata' => [
        'order_id' => $payment->getOrderId(),
        'store_id' => $payment->getOrder()->getStoreId(),
      ],
    ];

    // Add metadata and extra transaction data where required.
    $event = new TransactionDataEvent($payment);
    $this->eventDispatcher->dispatch(StripeEvents::TRANSACTION_DATA, $event);

    // Update the transaction data from additional information added through
    // the event.
    $transaction_data += $event->getTransactionData();
    $transaction_data['metadata'] += $event->getMetadata();

    $owner = $payment_method->getOwner();
    if ($owner && $owner->isAuthenticated()) {
      $transaction_data['customer'] = $this->getRemoteCustomerId($owner);
    }

    try {
      $result = \Stripe\Charge::create($transaction_data);
      ErrorHelper::handleErrors($result);
    }
    catch (\Stripe\Error\Base $e) {
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
      $charge = \Stripe\Charge::retrieve($remote_id);
      $charge->amount = $this->toMinorUnits($amount);
      $transaction_data = [
        'amount' => $charge->amount,
      ];
      $charge->capture($transaction_data);
    }
    catch (\Stripe\Error\Base $e) {
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

    // Void Stripe payment - release uncaptured payment.
    try {
      $remote_id = $payment->getRemoteId();
      $amount = $payment->getAmount();
      $data = [
        'charge' => $remote_id,
        'amount' => $this->toMinorUnits($amount),
      ];
      $release_refund = \Stripe\Refund::create($data);
      ErrorHelper::handleErrors($release_refund);
    }
    catch (\Stripe\Error\Base $e) {
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
      $data = [
        'charge' => $remote_id,
        'amount' => $this->toMinorUnits($amount),
      ];
      $refund = \Stripe\Refund::create($data);
      ErrorHelper::handleErrors($refund);
    }
    catch (\Stripe\Error\Base $e) {
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
      'stripe_token',
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
        $customer = \Stripe\Customer::retrieve($customer_id);
        $customer->sources->retrieve($payment_method->getRemoteId())->delete();
      }
    }
    catch (\Stripe\Error\Base $e) {
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
    $customer_data = [];
    if ($owner && $owner->isAuthenticated()) {
      $customer_id = $this->getRemoteCustomerId($owner);
      $customer_data['email'] = $owner->getEmail();
    }

    if ($customer_id) {
      // If the customer id already exists, use the Stripe form token to create the new card.
      $customer = \Stripe\Customer::retrieve($customer_id);
      // Create a payment method for an existing customer.
      try {
        $card = $customer->sources->create(['source' => $payment_details['stripe_token']]);
        return $card;
      }
      catch (\Stripe\Error\Base $e) {
        ErrorHelper::handleException($e);
      }
    }
    elseif ($owner && $owner->isAuthenticated()) {
      // Create both the customer and the payment method.
      try {
        $customer = \Stripe\Customer::create([
          'email' => $owner->getEmail(),
          'description' => $this->t('Customer for :mail', [':mail' => $owner->getEmail()]),
          'source' => $payment_details['stripe_token'],
        ]);
        $cards = \Stripe\Customer::retrieve($customer->id)->sources->all(['object' => 'card']);
        $cards_array = \Stripe\Util\Util::convertStripeObjectToArray([$cards]);
        $this->setRemoteCustomerId($owner, $customer->id);
        $owner->save();
        foreach ($cards_array[0]['data'] as $card) {
          return $card;
        }
      }
      catch (\Stripe\Error\Base $e) {
        ErrorHelper::handleException($e);
      }
    }
    else {
      $card_token = \Stripe\Token::retrieve($payment_details['stripe_token']);
      // We need to use token for Anonymous customers.
      $card_token->card['id'] = $payment_details['stripe_token'];
      return $card_token->card;
    }

    return [];
  }

  /**
   * Maps the Stripe credit card type to a Commerce credit card type.
   *
   * @param string $card_type
   *   The Stripe credit card type.
   *
   * @return string
   *   The Commerce credit card type.
   */
  protected function mapCreditCardType($card_type) {
    // https://support.stripe.com/questions/which-cards-and-payment-types-can-i-accept-with-stripe.
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

}
