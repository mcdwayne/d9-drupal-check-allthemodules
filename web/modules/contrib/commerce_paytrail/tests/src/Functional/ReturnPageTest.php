<?php

namespace Drupal\Tests\commerce_paytrail\Functional;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\commerce_paytrail\Repository\Response;
use Drupal\commerce_store\StoreCreationTrait;
use Drupal\Core\Url;
use Drupal\Tests\commerce_order\Functional\OrderBrowserTestBase;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ReturnPageTest.
 *
 * @group commerce_paytrail
 */
class ReturnPageTest extends OrderBrowserTestBase {

  use StoreCreationTrait;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_product',
    'commerce_cart',
    'commerce_checkout',
    'commerce_payment',
    'commerce_paytrail',
  ];

  /**
   * The payment manager.
   *
   * @var \Drupal\commerce_paytrail\PaymentManagerInterface
   */
  protected $paymentManager;

  /**
   * The payment gateway.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface
   */
  protected $gateway;

  /**
   * The merchant hash.
   *
   * @var string
   */
  protected $merchant_hash;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->gateway = PaymentGateway::create([
      'id' => 'paytrail',
      'label' => 'Paytrail',
      'plugin' => 'paytrail',
    ]);
    $this->merchant_hash = '6pKF4jkv97zmqBJ3ZL8gUw5DfT2NMQ';

    $this->gateway->getPlugin()->setConfiguration([
      'culture' => 'automatic',
      'merchant_id' => '13466',
      'merchant_hash' => $this->merchant_hash,
      'bypass_mode' => FALSE,
    ]);
    $this->gateway->save();

    $this->paymentManager = $this->container->get('commerce_paytrail.payment_manager');
  }

  /**
   * Builds the return url.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order.
   * @param string $type
   *   The return url type.
   * @param array $arguments
   *   The additional arguments.
   *
   * @return string
   *   The return url.
   */
  private function buildReturnUrl(OrderInterface $order, string $type, array $arguments = []) : string {
    $arguments = array_merge([
      'commerce_order' => $order->id(),
      'step' => $arguments['step'] ?? 'payment',
    ], $arguments);

    return (new Url($type, $arguments, ['absolute' => TRUE]))
      ->toString();
  }

  /**
   * Tests return callbacks.
   */
  public function testReturn() {
    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
      'unit_price' => [
        'number' => '999',
        'currency_code' => 'USD',
      ],
    ]);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'order_items' => [$order_item],
      'uid' => $this->loggedInUser,
      'store_id' => $this->store,
      'state' => 'draft',
      'checkout_flow' => 'default',
      'checkout_step' => 'payment',
      'payment_gateway' => 'paytrail',
    ]);
    $return_url = $this->buildReturnUrl($order, 'commerce_payment.checkout.return');

    $request = Request::createFromGlobals();

    $request->query = new ParameterBag([
      'ORDER_NUMBER' => $order->id(),
      'PAYMENT_ID' => '123d',
      'PAYMENT_METHOD' => '1',
      'TIMESTAMP' => \Drupal::time()->getRequestTime(),
      'STATUS' => 'CANCELLED',
      'RETURN_AUTHCODE' => '1234',
    ]);

    $response = Response::createFromRequest($this->merchant_hash, $order, $request);

    $query = $response->getHashValues();
    $query['RETURN_AUTHCODE'] = '1234';

    // Test with invalid payment state.
    $this->drupalGet($return_url, ['query' => $query]);
    $this->assertSession()->pageTextContains('Validation failed due to security hash mismatch (payment_state).');

    // Update order back to payment step.
    $order->set('checkout_step', 'payment')->save();

    // Update status to paid.
    $query['STATUS'] = 'PAID';

    $this->drupalGet($return_url, ['query' => $query]);
    $this->assertSession()->pageTextContains('Validation failed due to security hash mismatch (hash_mismatch).');

    // Update order back to payment step.
    $order->set('checkout_step', 'payment')->save();

    $request->query->set('STATUS', 'PAID');
    $response = Response::createFromRequest($this->merchant_hash, $order, $request);
    $authcode = $response->generateReturnChecksum($response->getHashValues());
    $query = $response->getHashValues();
    // Test with invalid order id.
    $query['RETURN_AUTHCODE'] = $authcode;
    $query['ORDER_NUMBER'] = 5;

    $this->drupalGet($return_url, ['query' => $query]);
    $this->assertSession()->pageTextContains('Validation failed due to security hash mismatch (order_number).');

    // Update order back to payment step.
    $order->set('checkout_step', 'payment')->save();

    $query['ORDER_NUMBER'] = $order->id();
    // Test correct return url.
    $this->drupalGet($return_url, ['query' => $query]);
    $this->assertSession()->pageTextContains('Payment was processed.');

    $payment = Payment::load(1);
    $this->assertEquals('authorization', $payment->getState()->value);
    $this->assertEquals($response->getPaymentId(), $payment->getRemoteId());
    $this->assertEquals('PAID', $payment->getRemoteState());
  }

  /**
   * Tests IPN callback.
   */
  public function testIpn() {
    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
      'unit_price' => [
        'number' => '999',
        'currency_code' => 'USD',
      ],
    ]);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'order_items' => [$order_item],
      'uid' => $this->loggedInUser,
      'store_id' => $this->store,
      'state' => 'draft',
      'checkout_flow' => 'default',
      'checkout_step' => 'payment',
      'payment_gateway' => 'paytrail',
    ]);
    $request = Request::createFromGlobals();
    // Test nofitifcation.
    $request->query = new ParameterBag([
      'ORDER_NUMBER' => '5',
      'PAYMENT_ID' => '123d',
      'PAYMENT_METHOD' => '1',
      'TIMESTAMP' => \Drupal::time()->getRequestTime(),
      'STATUS' => 'CANCELLED',
      'RETURN_AUTHCODE' => '1234',
    ]);

    $response = Response::createFromRequest($this->merchant_hash, $order, $request);
    $query = $response->getHashValues();

    $query['RETURN_AUTHCODE'] = '1234';

    $notify_url = $this->buildReturnUrl($order, 'commerce_payment.notify', [
      'commerce_payment_gateway' => 'paytrail',
    ]);

    // Test invalid order id.
    $this->drupalGet($notify_url, ['query' => $query]);
    $this->assertSession()->statusCodeEquals(404);

    // Test invalid payment state.
    $query['ORDER_NUMBER'] = $order->id();
    $this->drupalGet($notify_url, ['query' => $query]);
    $this->assertSession()->statusCodeEquals(400);
    $this->assertSession()->pageTextContains('Hash mismatch (payment_state).');

    // Test invalid hash.
    $query['STATUS'] = 'PAID';
    $this->drupalGet($notify_url, ['query' => $query]);
    $this->assertSession()->statusCodeEquals(400);
    $this->assertSession()->pageTextContains('Hash mismatch (hash_mismatch).');

    // Test with correct values.
    $request->query = new ParameterBag([
      'ORDER_NUMBER' => $order->id(),
      'PAYMENT_ID' => '123d',
      'PAYMENT_METHOD' => '1',
      'TIMESTAMP' => \Drupal::time()->getRequestTime(),
      'STATUS' => 'PAID',
      'RETURN_AUTHCODE' => '1234',
    ]);
    $response = Response::createFromRequest($this->merchant_hash, $order, $request);
    $query = $response->getHashValues();
    $query['RETURN_AUTHCODE'] = $response->generateReturnChecksum($response->getHashValues());

    // Make sure we get error when user didn't return from the payment service.
    $this->drupalGet($notify_url, ['query' => $query]);
    $this->assertSession()->statusCodeEquals(400);
    $this->assertSession()->pageTextContains('Invalid payment state.');

    // Call return url to create payment.
    $return_url = $this->buildReturnUrl($order, 'commerce_payment.checkout.return');
    $this->drupalGet($return_url, ['query' => $query]);

    $this->drupalGet($notify_url, ['query' => $query]);
    $this->assertSession()->statusCodeEquals(200);

    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    $entity_manager = $this->container->get('entity_type.manager');
    $entity_manager->getStorage('commerce_payment')->resetCache([1]);
    $payment = $entity_manager->getStorage('commerce_payment')->load(1);
    $this->assertEquals('completed', $payment->getState()->value);
    $this->assertEquals($response->getPaymentId(), $payment->getRemoteId());
    $this->assertEquals('PAID', $payment->getRemoteState());
  }

  /**
   * Tests IPN payment creation.
   */
  public function testIpnPayment() {
    $this->gateway->getPlugin()->setConfiguration([
      'allow_ipn_create_payment' => TRUE,
    ]);
    $this->gateway->save();

    $order_item = $this->createEntity('commerce_order_item', [
      'type' => 'default',
      'unit_price' => [
        'number' => '999',
        'currency_code' => 'USD',
      ],
    ]);
    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $this->createEntity('commerce_order', [
      'type' => 'default',
      'mail' => $this->loggedInUser->getEmail(),
      'order_items' => [$order_item],
      'uid' => $this->loggedInUser,
      'store_id' => $this->store,
      'state' => 'draft',
      'checkout_flow' => 'default',
      'checkout_step' => 'payment',
      'payment_gateway' => 'paytrail',
    ]);
    $request = Request::createFromGlobals();

    $notify_url = $this->buildReturnUrl($order, 'commerce_payment.notify', [
      'commerce_payment_gateway' => 'paytrail',
    ]);

    // Test with correct values.
    $request->query = new ParameterBag([
      'ORDER_NUMBER' => $order->id(),
      'PAYMENT_ID' => '123d',
      'PAYMENT_METHOD' => '1',
      'TIMESTAMP' => \Drupal::time()->getRequestTime(),
      'STATUS' => 'PAID',
      'RETURN_AUTHCODE' => '1234',
    ]);
    $response = Response::createFromRequest($this->merchant_hash, $order, $request);
    $query = $response->getHashValues();
    $query['RETURN_AUTHCODE'] = $response->generateReturnChecksum($response->getHashValues());

    $this->drupalGet($notify_url, ['query' => $query]);
    $this->assertSession()->statusCodeEquals(200);

    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    $entity_manager = $this->container->get('entity_type.manager');
    $entity_manager->getStorage('commerce_payment')->resetCache([1]);
    $payment = $entity_manager->getStorage('commerce_payment')->load(1);
    $this->assertEquals('completed', $payment->getState()->value);
    $this->assertEquals($response->getPaymentId(), $payment->getRemoteId());
    $this->assertEquals('PAID', $payment->getRemoteState());

    // Make sure payment state won't be overridden when calling return again.
    $return_url = $this->buildReturnUrl($order, 'commerce_payment.checkout.return');
    $this->drupalGet($return_url, ['query' => $query]);

    $entity_manager->getStorage('commerce_payment')->resetCache([1]);
    $payment = $entity_manager->getStorage('commerce_payment')->load(1);
    $this->assertEquals('completed', $payment->getState()->value);
  }

}
