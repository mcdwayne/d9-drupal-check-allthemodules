<?php

namespace Drupal\Tests\commerce_braintree\Kernel;

use Drupal\commerce_braintree\Plugin\Commerce\PaymentGateway\HostedFields;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_payment\Entity\PaymentMethod;
use Drupal\commerce_payment\Exception\HardDeclineException;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_payment\Exception\SoftDeclineException;
use Drupal\commerce_price\Price;
use Drupal\profile\Entity\Profile;
use Drupal\Tests\commerce\Kernel\CommerceKernelTestBase;

/**
 * Tests the Braintree SDK integration
 *
 * @group commerce_braintree
 */
class BraintreeApiIntegrationTest extends CommerceKernelTestBase {

  public static $modules = [
    'profile',
    'entity_reference_revisions',
    'state_machine',
    'commerce_order',
    'commerce_payment',
    'commerce_braintree',
  ];

  /**
   * The test gateway.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGateway
   */
  protected $gateway;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('profile');
    $this->installEntitySchema('commerce_order');
    $this->installEntitySchema('commerce_order_item');
    $this->installEntitySchema('commerce_payment');
    $this->installEntitySchema('commerce_payment_method');
    $this->installConfig('commerce_order');
    $this->installConfig('commerce_payment');

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'braintree',
      'label' => 'Braintree',
      'plugin' => 'braintree_hostedfields',
    ]);
    $gateway->getPlugin()->setConfiguration([
      'merchant_id' => 'hy3tktc463w6g7pw',
      'public_key' => 'fsspfgwhnm6by9gk',
      'private_key' => '671d13c9dee5815425f954df590bfc98',
      'merchant_account_id' => [
        'USD' => 'commerceguys',
      ],
      'display_label' => 'Braintree',
      'payment_method_types' => ['credit_card'],
    ]);
    $gateway->save();
    $this->gateway = $gateway;
  }

  public function testGatewayConstruction() {
    $plugin = $this->gateway->getPlugin();
    $this->assertTrue($plugin instanceof HostedFields);
  }

  /**
   * Tests creating a payment.
   */
  public function testCreatePayment() {
    /** @var \Drupal\commerce_braintree\Plugin\Commerce\PaymentGateway\HostedFieldsInterface $gateway_plugin */
    $gateway_plugin = $this->gateway->getPlugin();
    $gateway_plugin->createPayment($this->generateTestPayment('10.00'));
  }

  /**
   * Tests creating a payment, with insufficient funds.
   */
  public function testCreatePaymentInsufficentFunds() {
    $this->setExpectedException(SoftDeclineException::class, 'Insufficient Funds (2001 : Insufficient Funds)');
    /** @var \Drupal\commerce_braintree\Plugin\Commerce\PaymentGateway\HostedFieldsInterface $gateway_plugin */
    $gateway_plugin = $this->gateway->getPlugin();
    $gateway_plugin->createPayment($this->generateTestPayment('2001.00'));
    throw new \Exception('Charge should not have been successful.');
  }

  /**
   * Tests creating a payment, with processor declined.
   */
  public function testCreatePaymentProcessorDeclined() {
    $this->setExpectedException(SoftDeclineException::class, 'Processor Declined (2101 : )');
    /** @var \Drupal\commerce_braintree\Plugin\Commerce\PaymentGateway\HostedFieldsInterface $gateway_plugin */
    $gateway_plugin = $this->gateway->getPlugin();
    $gateway_plugin->createPayment($this->generateTestPayment('2101.00'));
    throw new \Exception('Charge should not have been successful.');
  }

  /**
   * Tests creating a payment, with application incomplete.
   */
  public function testCreatePaymentApplicationIncomplete() {
    $this->setExpectedException(HardDeclineException::class, 'Rejected by the gateway. Reason: application_incomplete');
    /** @var \Drupal\commerce_braintree\Plugin\Commerce\PaymentGateway\HostedFieldsInterface $gateway_plugin */
    $gateway_plugin = $this->gateway->getPlugin();
    $gateway_plugin->createPayment($this->generateTestPayment('5001.00'));
    throw new \Exception('Charge should not have been successful.');
  }

  /**
   * Tests a re-used nonce.
   */
  public function testCreatePaymentRejectedConsumedNonce() {
    $this->setExpectedException(InvalidRequestException::class, 'Cannot use a paymentMethodNonce more than once.');
    /** @var \Drupal\commerce_braintree\Plugin\Commerce\PaymentGateway\HostedFieldsInterface $gateway_plugin */
    $gateway_plugin = $this->gateway->getPlugin();
    $gateway_plugin->createPayment($this->generateTestPayment('10.00', \Braintree\Test\Nonces::$consumed));
  }

  /**
   * Generates a test payment to send over the Braintree gateway.
   *
   * @param string $amount
   *   The test amount.
   * @param string $nonce
   *   The test nonce.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface The test payment.
   * The test payment.
   *
   * @see https://developers.braintreepayments.com/reference/general/testing/php#test-amounts
   */
  protected function generateTestPayment($amount, $nonce = NULL) {
    if ($nonce === NULL) {
      $nonce = \Braintree\Test\Nonces::$transactable;
    }

    $user = $this->createUser();
    $order = Order::create([
      'type' => 'default',
      'store_id' => $this->store->id(),
      'state' => 'draft',
      'mail' => 'text@example.com',
      'uid' => $user->id(),
      'ip_address' => '127.0.0.1',
    ]);
    $order->save();

    /** @var \Drupal\profile\Entity\ProfileInterface $profile */
    $profile = Profile::create([
      'type' => 'customer',
      'address' => [
        'country_code' => 'US',
        'postal_code' => '53177',
        'locality' => 'Milwaukee',
        'address_line1' => 'Pabst Blue Ribbon Dr',
        'administrative_area' => 'WI',
        'given_name' => 'Frederick',
        'family_name' => 'Pabst',
      ],
      'uid' => $user->id(),
    ]);
    $profile->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $this->gateway->id(),
      'uid' => $user->id(),
      'remote_id' => $nonce,
    ]);
    $payment_method->setBillingProfile($profile);
    $payment_method->setReusable(FALSE);
    $payment_method->save();

    $payment = Payment::create([
      'state' => 'new',
      'amount' => new Price($amount, 'USD'),
      'payment_gateway' => $this->gateway->id(),
      'order_id' => $order->id(),
    ]);
    $payment->payment_method = $payment_method;
    return $payment;
  }

}
