<?php

namespace Drupal\Tests\commerce_mollie\Functional;

use Drupal\commerce_order\Entity\OrderType;
use Drupal\commerce_payment\Entity\PaymentGateway;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Mollie\Api\Types\PaymentStatus as MolliePaymentStatus;

/**
 * Tests the checkout form that initializes a payment.
 *
 * @group commerce_mollie
 */
class MolliePaymentOffsiteFormTest extends CommerceBrowserTestBase {

  /**
   * A manual payment gateway.
   *
   * @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface
   */
  protected $paymentGateway;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'commerce_product',
    'commerce_order',
    'commerce_cart',
    'commerce_checkout',
    'commerce_payment',
    'commerce_mollie',
    'commerce_mollie_tests',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => '29.99',
        'currency_code' => 'USD',
      ],
    ]);

    /** @var \Drupal\commerce_product\Entity\ProductInterface $product */
    $this->product = $this->createEntity('commerce_product', [
      'type' => 'default',
      'title' => 'My product',
      'variations' => [$variation],
      'stores' => [$this->store],
    ]);

    /** @var \Drupal\commerce_order\Entity\OrderType $order_type */
    $order_type = OrderType::load('default');
    $order_type->setWorkflowId('order_default_validation');
    $order_type->save();

    /** @var \Drupal\commerce_payment\Entity\PaymentGateway $gateway */
    $gateway = PaymentGateway::create([
      'id' => 'mollie_test_gateway',
      'label' => 'Mollie',
      'plugin' => 'mollie',
    ]);
    $gateway->getPlugin()->setConfiguration([
      'mode' => 'test',
      'api_key_test' => 'test_Dfm3kc8CNcFb34xHnxwNNEyAJTteez',
      'api_key_live' => 'live_key',
      'callback_domain' => 'https://molliedevelopment.localtunnel.me',
    ]);
    $gateway->save();

    // Cheat so we don't need JS to interact w/ Address field widget.
    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $customer_form_display */
    $customer_form_display = EntityFormDisplay::load('profile.customer.default');
    $address_component = $customer_form_display->getComponent('address');
    $address_component['settings']['default_country'] = 'US';
    $customer_form_display->setComponent('address', $address_component);
    $customer_form_display->save();
  }

  /**
   * Tests MOLLIE payment with PAID status.
   *
   * /Drupal/commerce_mollie_tests/Services/MollieApiMock returns PAID when
   * the order amount is exactly "29.99" (1x the test-product with price 29.99).
   *
   * @todo #2950538 Mock Mollie callback in test-suite
   */
  public function testMolliePaymentStatusPaid() {

    // 1x Add a product to the cart.
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');

    // Goto checkout, proceed payment (go to Mollie).
    $this->helperGotoCheckoutAndProceedPayment();

    // Validations before onNotify() and onReturn().
    $this->helperValidateStatus([
      'commerce_order_status' => 'draft',
      'commerce_order_isPaid' => FALSE,
      'commerce_payment_status' => 'new',
      'commerce_payment_remoteStatus' => MolliePaymentStatus::STATUS_PAID,
    ]);

    // First call onNotify().
    $this->helperCallOnNotifyWebhook();

    // Then do onReturn() (go back to website).
    $this->drupalGet('mollie_return/1');
    $this->assertSession()->addressEquals('checkout/1/complete');
    $this->assertSession()->pageTextContains('Your order number is 1. You can view your order on your account page when logged in.');
    $this->assertSession()->pageTextContains('Thank you for your payment with Mollie. We will inform you when your payment is processed. This is usually done within 24 hours.');

    // Validations after onNotify() and onReturn().
    $this->helperValidateStatus([
      'commerce_order_status' => 'validation',
      'commerce_order_isPaid' => TRUE,
      'commerce_payment_status' => 'completed',
      'commerce_payment_remoteStatus' => MolliePaymentStatus::STATUS_PAID,
    ]);
  }

  /**
   * Tests MOLLIE payment with CANCELED status.
   *
   * /Drupal/commerce_mollie_tests/Services/MollieApiMock returns CANCELED when
   * the order amount is exactly "59.98" (2x the test-product with price 29.99).
   *
   * @todo #2950538 Mock Mollie callback in test-suite
   */
  public function testMolliePaymentStatusCanceled() {

    // 2x Add a product to the cart.
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');

    // Goto checkout, proceed payment (go to Mollie).
    $this->helperGotoCheckoutAndProceedPayment();

    // Validations before onNotify() and onReturn().
    $this->helperValidateStatus([
      'commerce_order_status' => 'draft',
      'commerce_order_isPaid' => FALSE,
      'commerce_payment_status' => 'new',
      'commerce_payment_remoteStatus' => MolliePaymentStatus::STATUS_CANCELED,
    ]);

    // First call onNotify().
    $this->helperCallOnNotifyWebhook();

    // Then do onReturn() (go back to website).
    $this->drupalGet('mollie_return/1');
    $this->assertSession()->addressEquals('checkout/1/review');
    $this->assertSession()->pageTextContains('You have canceled checkout at Mollie but may resume the checkout process here when you are ready.');

    // Validations after onNotify() and onReturn().
    $this->helperValidateStatus([
      'commerce_order_status' => 'draft',
      'commerce_order_isPaid' => FALSE,
      'commerce_payment_status' => 'authorization_voided',
      'commerce_payment_remoteStatus' => MolliePaymentStatus::STATUS_CANCELED,
    ]);
  }

  /**
   * Tests MOLLIE payment with OPEN status.
   *
   * /Drupal/commerce_mollie_tests/Services/MollieApiMock returns OPEN when
   * the order amount is exactly "89.97" (3x the test-product with price 29.99).
   *
   * @todo #2950538 Mock Mollie callback in test-suite
   */
  public function testMolliePaymentStatusOpen() {

    // 3x Add a product to the cart.
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');

    // Goto checkout, proceed payment (go to Mollie).
    $this->helperGotoCheckoutAndProceedPayment();

    // Validations before onNotify() and onReturn().
    $this->helperValidateStatus([
      'commerce_order_status' => 'draft',
      'commerce_order_isPaid' => FALSE,
      'commerce_payment_status' => 'new',
      'commerce_payment_remoteStatus' => MolliePaymentStatus::STATUS_OPEN,
    ]);

    // First call onNotify().
    $this->helperCallOnNotifyWebhook();

    // Then do onReturn() (go back to website).
    $this->drupalGet('mollie_return/1');
    $this->assertSession()->addressEquals('mollie_return/1');
    $this->assertSession()->pageTextContains('We have not yet received the payment status from Mollie. Please reload this page.');

    // Validations after onNotify() and onReturn().
    $this->helperValidateStatus([
      'commerce_order_status' => 'draft',
      'commerce_order_isPaid' => FALSE,
      'commerce_payment_status' => 'authorization',
      'commerce_payment_remoteStatus' => MolliePaymentStatus::STATUS_OPEN,
    ]);
  }

  /**
   * Tests MOLLIE payment with FAILED status.
   *
   * /Drupal/commerce_mollie_tests/Services/MollieApiMock returns FAILED when
   * the order amount is exactly "119.96" (4x the test-product with price 29.99).
   *
   * @todo #2950538 Mock Mollie callback in test-suite
   */
  public function testMolliePaymentStatusFailed() {

    // 4x Add a product to the cart.
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');

    // Goto checkout, proceed payment (go to Mollie).
    $this->helperGotoCheckoutAndProceedPayment();

    // Validations before onNotify() and onReturn().
    $this->helperValidateStatus([
      'commerce_order_status' => 'draft',
      'commerce_order_isPaid' => FALSE,
      'commerce_payment_status' => 'new',
      'commerce_payment_remoteStatus' => MolliePaymentStatus::STATUS_FAILED,
    ]);

    // First call onNotify().
    $this->helperCallOnNotifyWebhook();

    // Then do onReturn() (go back to website).
    $this->drupalGet('mollie_return/1');
    $this->assertSession()->addressEquals('checkout/1/review');
    $this->assertSession()->pageTextContains('Your payment at Mollie has failed. You may resume the checkout process here when you are ready.');
    $this->assertSession()->pageTextContains('You have canceled checkout at Mollie but may resume the checkout process here when you are ready.');

    // Validations after onNotify() and onReturn().
    $this->helperValidateStatus([
      'commerce_order_status' => 'draft',
      'commerce_order_isPaid' => FALSE,
      'commerce_payment_status' => 'authorization_voided',
      'commerce_payment_remoteStatus' => MolliePaymentStatus::STATUS_FAILED,
    ]);
  }

  /**
   * Tests MOLLIE payment with EXPIRED status.
   *
   * /Drupal/commerce_mollie_tests/Services/MollieApiMock returns FAILED when
   * the order amount is exactly "149.95" (5x the test-product with price 29.99).
   *
   * @todo #2950538 Mock Mollie callback in test-suite
   */
  public function testMolliePaymentStatusExpired() {

    // 5x Add a product to the cart.
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
    $this->drupalGet($this->product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');

    // Goto checkout, proceed payment (go to Mollie).
    $this->helperGotoCheckoutAndProceedPayment();

    // Validations before onNotify() and onReturn().
    $this->helperValidateStatus([
      'commerce_order_status' => 'draft',
      'commerce_order_isPaid' => FALSE,
      'commerce_payment_status' => 'new',
      'commerce_payment_remoteStatus' => MolliePaymentStatus::STATUS_EXPIRED,
    ]);

    // First call onNotify().
    $this->helperCallOnNotifyWebhook();

    // Then do onReturn() (go back to website).
    $this->drupalGet('mollie_return/1');
    $this->assertSession()->addressEquals('checkout/1/review');
    $this->assertSession()->pageTextContains('Your payment at Mollie has expired. You may resume the checkout process here when you are ready.');
    $this->assertSession()->pageTextContains('You have canceled checkout at Mollie but may resume the checkout process here when you are ready.');

    // Validations after onNotify() and onReturn().
    $this->helperValidateStatus([
      'commerce_order_status' => 'draft',
      'commerce_order_isPaid' => FALSE,
      'commerce_payment_status' => 'authorization_expired',
      'commerce_payment_remoteStatus' => MolliePaymentStatus::STATUS_EXPIRED,
    ]);
  }

  /**
   * Go to checkout, proceed payment (go to Mollie).
   */
  protected function helperGotoCheckoutAndProceedPayment() {
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->submitForm([
      'payment_information[billing_information][address][0][address][given_name]' => 'Johnny',
      'payment_information[billing_information][address][0][address][family_name]' => 'Appleseed',
      'payment_information[billing_information][address][0][address][address_line1]' => '123 New York Drive',
      'payment_information[billing_information][address][0][address][locality]' => 'New York City',
      'payment_information[billing_information][address][0][address][administrative_area]' => 'NY',
      'payment_information[billing_information][address][0][address][postal_code]' => '10001',
    ], 'Continue to review');
    $this->assertSession()->pageTextContains('Contact information');
    $this->assertSession()->pageTextContains($this->loggedInUser->getEmail());
    $this->assertSession()->pageTextContains('Payment information');
    $this->assertSession()->pageTextContains('Order Summary');
    $this->submitForm([], 'Pay and complete purchase');
  }

  /**
   * Calls onNotify() a.k.a. Webhook.
   */
  protected function helperCallOnNotifyWebhook() {
    $notify_url = $this->getAbsoluteUrl('/payment/notify/mollie_test_gateway');
    $post_data = ['id' => 'test_id'];
    $session = $this->getSession();
    $session->setCookie('SIMPLETEST_USER_AGENT', drupal_generate_test_ua($this->databasePrefix));
    $session->getDriver()->getClient()->request('POST', $notify_url, $post_data);
  }

  /**
   * Validates Order and Payments statuses.
   */
  protected function helperValidateStatus($validations = []) {
    /** @var \Drupal\commerce_order\Entity\Order $order */
    $order = entity_load('commerce_order', 1, TRUE);
    /** @var \Drupal\commerce_payment\Entity\Payment $payment */
    $payment = entity_load('commerce_payment', 1, TRUE);

    // Order validations.
    if (array_key_exists('commerce_order_status', $validations)) {
      $this->assertEquals($validations['commerce_order_status'], $order->getState()->value);
    }
    if (array_key_exists('commerce_order_isPaid', $validations)) {
      $this->assertEquals($validations['commerce_order_isPaid'], $order->isPaid());
    }
    // Payment validations.
    if (array_key_exists('commerce_payment_status', $validations)) {
      $this->assertEquals($validations['commerce_payment_status'], $payment->getState()->value);
    }
    if (array_key_exists('commerce_payment_remoteStatus', $validations)) {
      $this->assertEquals($validations['commerce_payment_remoteStatus'], $payment->getRemoteState());
    }
  }

}
