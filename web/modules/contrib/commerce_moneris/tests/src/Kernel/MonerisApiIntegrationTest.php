<?php

namespace Drupal\Tests\commerce_moneris\Kernel;

use Drupal\commerce_moneris\Plugin\Commerce\PaymentGateway\Onsite;
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
 * Tests the Moneris SDK integration.
 *
 * @group commerce_moneris
 */
class MonerisApiIntegrationTest extends CommerceKernelTestBase {

  public static $modules = [
    'profile',
    'entity_reference_revisions',
    'state_machine',
    'commerce_order',
    'commerce_payment',
    'commerce_moneris',
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
      'id' => 'moneris',
      'label' => 'moneris',
      'plugin' => 'moneris_onsite',
    ]);
    $gateway->getPlugin()->setConfiguration([
      'store_id' => 'store5',
      'api_token' => 'yesguy',
      'country_code' => 'CA',
      'should_pass_avs_info' => TRUE,
      'display_label' => 'Moneris',
      'payment_method_types' => ['credit_card'],
    ]);
    $gateway->save();
    $this->gateway = $gateway;
  }

  public function testGatewayConstruction() {
    $plugin = $this->gateway->getPlugin();
    $this->assertTrue($plugin instanceof Onsite);
  }

  /**
   * Tests creating a payment.
   */
  public function testCreatePayment() {
    /** @var \Drupal\commerce_moneris\Plugin\Commerce\PaymentGateway\OnsiteInterface $gateway_plugin */
    $gateway_plugin = $this->gateway->getPlugin();
    $gateway_plugin->createPayment($this->generateTestPayment('10.10'));
  }

  /**
   * Tests creating a payment, with processor declined.
   */
  public function testCreatePaymentProcessorDeclined() {
    $this->setExpectedException(HardDeclineException::class, 'Response Code: 481 - DECLINED           *                    =');
    /** @var \Drupal\commerce_moneris\Plugin\Commerce\PaymentGateway\OnsiteInterface $gateway_plugin */
    $gateway_plugin = $this->gateway->getPlugin();
    $gateway_plugin->createPayment($this->generateTestPayment('10.24'));
    throw new \Exception('Charge should not have been successful.');
  }

  /**
   * Generates a test payment to send over the Moneris gateway.
   *
   * @param string $amount
   *   The test amount.
   *
   * @return \Drupal\commerce_payment\Entity\PaymentInterface
   *   The test payment.
   *
   * @see https://developer.moneris.com/en/Documentation/NA/E-Commerce%20Solutions/API/~/link.aspx?_id=96891BFCE34F4C7FB2BA6DDF6BA4EC0C&_z=z
   */
  protected function generateTestPayment($amount) {
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
        'country_code' => 'CA',
        'postal_code' => 'K1A0A2',
        'locality' => 'Ottawa',
        'address_line1' => '80 Wellington St',
        'administrative_area' => 'ON',
        'given_name' => 'Justin',
        'family_name' => 'Trudeau',
      ],
      'uid' => $user->id(),
    ]);
    $profile->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method */
    $payment_method = PaymentMethod::create([
      'type' => 'credit_card',
      'payment_gateway' => $this->gateway->id(),
      'uid' => $user->id(),
    ]);

    $payment_method->setBillingProfile($profile);
    $payment_method->setReusable(FALSE);
    $payment_method->save();

    $details = [
      'type' => 'visa',
      'number' => '4242424242424242',
      'expiration' => ['month' => '01', 'year' => date("Y") + 1],
    ];

    $this->gateway->getPlugin()->createPaymentMethod($payment_method, $details);
    $this->gateway->save();

    $payment = Payment::create([
      'state' => 'new',
      'amount' => new Price($amount, 'CAD'),
      'payment_gateway' => $this->gateway->id(),
      'order_id' => $order->id(),
    ]);
    $payment->payment_method = $payment_method;
    return $payment;
  }

}
