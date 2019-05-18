<?php

namespace Drupal\Tests\commerce_abandoned_carts\Functional;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Test\AssertMailTrait;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\Tests\Traits\Core\CronRunTrait;

/**
 * Tests that customers that abandoned their carts receive a mail.
 *
 * @group commerce_abandoned_carts
 */
class MailTest extends CommerceBrowserTestBase {

  use AssertMailTrait;
  use CronRunTrait;

  /**
   * The number of minutes in one day.
   *
   * @var int
   */
  const SECONDS_IN_DAY = 86400;

  /**
   * {@inheritdoc}
   */
  public static $modules = ['commerce_abandoned_carts', 'language'];

  /**
   * The product.
   *
   * @var \Drupal\commerce_product\Entity\ProductInterface
   */
  protected $product;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->placeBlock('commerce_cart');
    $this->placeBlock('commerce_checkout_progress');

    $variation = $this->createEntity('commerce_product_variation', [
      'type' => 'default',
      'sku' => strtolower($this->randomMachineName()),
      'price' => [
        'number' => 9.99,
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

    // Set mail sender for mailsystem module to the test mail collector. This
    // prevents tests from sending out emails and collect them in state instead.
    $this->config('mailsystem.settings')->set('defaults.sender', 'test_mail_collector')->save();

    // Disable test mode.
    $this->config('commerce_abandoned_carts.settings')
      ->set('testmode', FALSE)
      ->save();
  }

  /**
   * Adds the given product to the cart.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product to add to the cart.
   */
  protected function addProductToCart(ProductInterface $product) {
    $this->drupalGet($product->toUrl()->toString());
    $this->submitForm([], 'Add to cart');
  }

  /**
   * Proceeds to checkout.
   */
  protected function goToCheckout() {
    $cart_link = $this->getSession()->getPage()->findLink('your cart');
    $cart_link->click();
    $this->submitForm([], 'Checkout');
  }

  /**
   * Asserts the current step in the checkout progress block.
   *
   * @param string $expected
   *   The expected value.
   */
  protected function assertCheckoutProgressStep($expected) {
    $current_step = $this->getSession()->getPage()->find('css', '.checkout-progress--step__current')->getText();
    $this->assertEquals($expected, $current_step);
  }

  /**
   * Processes order information step.
   *
   * @param bool $new_customer
   *   Whether or not a new customer is checking out. Defaults to true.
   */
  protected function processOrderInformation($new_customer = TRUE) {
    $edit = [
      'billing_information[profile][address][0][address][given_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][family_name]' => $this->randomString(),
      'billing_information[profile][address][0][address][organization]' => $this->randomString(),
      'billing_information[profile][address][0][address][address_line1]' => $this->randomString(),
      'billing_information[profile][address][0][address][postal_code]' => '94043',
      'billing_information[profile][address][0][address][locality]' => 'Mountain View',
      'billing_information[profile][address][0][address][administrative_area]' => 'CA',
    ];
    if ($new_customer) {
      $edit += [
        'contact_information[email]' => 'guest@example.com',
        'contact_information[email_confirm]' => 'guest@example.com',
      ];
    }

    // Add order information.
    $this->assertCheckoutProgressStep('Order information');
    $this->submitForm($edit, 'Continue to review');
  }

  /**
   * Sets the order's changed date to a particular timestamp.
   *
   * @param int $order_id
   *   The ID of the order to update.
   * @param int $timestamp
   *   The timestamp to set for the order's changed date.
   */
  protected function setOrderChangedDate($order_id, $timestamp) {
    \Drupal::database()->update('commerce_order')
      ->fields([
        'changed' => $timestamp,
      ])
      ->condition('order_id', $order_id)
      ->execute();
  }

  /**
   * Tests mailing anonymous customers that abandoned their cart.
   */
  public function testWithAnonymousCustomer() {
    $this->drupalLogout();
    $this->addProductToCart($this->product);

    // Checkout as guest and stop at review step.
    $this->goToCheckout();
    $this->assertCheckoutProgressStep('Login');
    $this->submitForm([], 'Continue as Guest');
    $this->processOrderInformation();
    $this->assertCheckoutProgressStep('Review');

    // Manually set the order's last update date to one day ago.
    $order = Order::load(1);
    $this->setOrderChangedDate(1, $order->changed->value - static::SECONDS_IN_DAY);

    // Run cron.
    $this->cronRun();

    // Assert that the customer received an email.
    $mails = $this->getMails(['key' => 'abandoned_cart']);
    $this->assertEquals('guest@example.com', $mails[0]['to']);

    // Run cron again to ensure that the customer only receives one mail.
    $this->cronRun();
    $this->assertCount(1, $this->getMails(), 'The expected number of emails sent.');
  }

  /**
   * Tests mailing authenticated customers that abandoned their cart.
   */
  public function testWithAuthenticatedCustomer() {
    // Customer adds a product to the cart, goes to checkout, but then stops.
    $this->drupalLogout();
    $account = $this->drupalCreateUser(['access content'], 'customer');
    $this->drupalLogin($account);
    $this->addProductToCart($this->product);
    $this->goToCheckout();

    // Manually set the order's last update date to one day ago.
    $order = Order::load(1);
    $this->setOrderChangedDate(1, $order->changed->value - static::SECONDS_IN_DAY);

    // Run cron.
    $this->cronRun();

    // Assert that the customer received an email.
    $mails = $this->getMails(['key' => 'abandoned_cart']);
    $this->assertEquals('customer@example.com', $mails[0]['to']);

    // Run cron again to ensure that the customer only receives one mail.
    $this->cronRun();
    $this->assertCount(1, $this->getMails(), 'The expected number of emails sent.');
  }

  /**
   * Tests if a customer does *not* receive an email if they emptied their cart.
   */
  public function testNoMailOnAbandonedEmptyCarts() {
    // Customer adds a product to the cart and goes to checkout.
    $this->drupalLogout();
    $account = $this->drupalCreateUser(['access content'], 'customer');
    $this->drupalLogin($account);
    $this->addProductToCart($this->product);
    $this->goToCheckout();

    // Customer stops and empties their cart.
    $this->drupalGet('cart');
    $edit = [
      'edit_quantity[0]' => 0,
    ];
    $this->submitForm($edit, t('Update cart'));

    // Manually set the order's last update date to one day ago.
    $order = Order::load(1);
    $this->setOrderChangedDate(1, $order->changed->value - static::SECONDS_IN_DAY);

    // Run cron.
    $this->cronRun();

    // Assert no mails sent.
    $this->assertEmpty($this->getMails(['key' => 'abandoned_cart']), 'No emails have been sent.');
  }

  /**
   * Tests that a customer is not mailed when they recently updated their cart.
   */
  public function testNoMailOnRecentlyUpdatedCarts() {
    // Customer adds a product to the cart, goes to checkout, but then stops.
    $this->drupalLogout();
    $account = $this->drupalCreateUser(['access content'], 'customer');
    $this->drupalLogin($account);
    $this->addProductToCart($this->product);
    $this->goToCheckout();

    // Manually set the order's last update date to half a day ago.
    $order = Order::load(1);
    $this->setOrderChangedDate(1, $order->changed->value - static::SECONDS_IN_DAY / 2);

    // Run cron.
    $this->cronRun();

    // Assert no mails sent.
    $this->assertEmpty($this->getMails(['key' => 'abandoned_cart']), 'No emails have been sent.');
  }

  /**
   * Tests with changing timeout setting.
   */
  public function testWithOtherTimeoutSetting() {
    // Set time out to 60 minutes.
    $this->config('commerce_abandoned_carts.settings')->set('timeout', 60)->save();

    // Customer adds a product to the cart, goes to checkout, but then stops.
    $account = $this->drupalCreateUser(['access content'], 'customer');
    $this->drupalLogin($account);
    $this->addProductToCart($this->product);
    $this->goToCheckout();

    // Manually set the order's last update to 60 minutes ago.
    $order = Order::load(1);
    $this->setOrderChangedDate(1, $order->changed->value - 3600);

    // Run cron.
    $this->cronRun();

    // Assert that the customer received an email.
    $mails = $this->getMails(['key' => 'abandoned_cart']);
    $this->assertEquals('customer@example.com', $mails[0]['to']);
  }

  /**
   * Tests with BCC enabled.
   */
  public function testWithBcc() {
    // Set bcc mail address.
    $this->config('commerce_abandoned_carts.settings')
      ->set('bcc_active', TRUE)
      ->set('bcc_email', 'bcc@example.com')
      ->save();

    // Customer adds a product to the cart, goes to checkout, but then stops.
    $account = $this->drupalCreateUser(['access content'], 'customer');
    $this->drupalLogin($account);
    $this->addProductToCart($this->product);
    $this->goToCheckout();

    // Manually set the order's last update date to a day ago.
    $order = Order::load(1);
    $this->setOrderChangedDate(1, $order->changed->value - static::SECONDS_IN_DAY);

    // Run cron.
    $this->cronRun();

    // Assert that mail was sent bcc.
    $mails = $this->getMails(['key' => 'abandoned_cart']);
    $this->assertEquals('bcc@example.com', $mails[0]['headers']['Bcc']);

    // Now deactivate bcc.
    $this->config('commerce_abandoned_carts.settings')
      ->set('bcc_active', FALSE)
      ->save();

    // An other customer abandones their cart.
    $account = $this->drupalCreateUser(['access content'], 'customer2');
    $this->drupalLogin($account);
    $this->addProductToCart($this->product);
    $this->goToCheckout();

    // Manually set the order's last update date to a day ago.
    $order = Order::load(2);
    $this->setOrderChangedDate(2, $order->changed->value - static::SECONDS_IN_DAY);

    // Run cron.
    $this->cronRun();

    // Assert that mail was *not* sent bcc.
    $mails = $this->getMails(['key' => 'abandoned_cart']);
    $this->assertTrue(empty($mails[1]['headers']['Bcc']));
  }

  /**
   * Tests with test mode enabled.
   */
  public function testWithTestMode() {
    $this->config('commerce_abandoned_carts.settings')
      ->set('testmode', TRUE)
      ->set('testmode_email', 'test@example.com')
      ->save();

    // Customer adds a product to the cart, goes to checkout, but then stops.
    $account = $this->drupalCreateUser(['access content'], 'customer');
    $this->drupalLogin($account);
    $this->addProductToCart($this->product);
    $this->goToCheckout();

    // Manually set the order's last update date to a day ago.
    $order = Order::load(1);
    $this->setOrderChangedDate(1, $order->changed->value - static::SECONDS_IN_DAY);

    // Run cron.
    $this->cronRun();

    // Assert that the mail was sent to the test mail address only.
    $mails = $this->getMails(['key' => 'abandoned_cart']);
    $this->assertEquals('test@example.com', $mails[0]['to']);
    $this->assertCount(1, $mails);

    // Now deactivate test mode.
    $this->config('commerce_abandoned_carts.settings')
      ->set('testmode', FALSE)
      ->save();

    // Run cron again.
    $this->cronRun();

    // Assert that the customer now received a mail.
    $mails = $this->getMails(['key' => 'abandoned_cart']);
    $this->assertEquals('customer@example.com', $mails[1]['to']);
    $this->assertCount(2, $mails);
  }

  /**
   * Tests with test mode enabled, but without mail address.
   */
  public function testWithTestModeAndWithoutMailAddress() {
    $this->config('commerce_abandoned_carts.settings')
      ->set('testmode', TRUE)
      ->save();

    // Customer adds a product to the cart, goes to checkout, but then stops.
    $account = $this->drupalCreateUser(['access content'], 'customer');
    $this->drupalLogin($account);
    $this->addProductToCart($this->product);
    $this->goToCheckout();

    // Manually set the order's last update date to a day ago.
    $order = Order::load(1);
    $this->setOrderChangedDate(1, $order->changed->value - static::SECONDS_IN_DAY);

    // Run cron.
    $this->cronRun();

    // Assert no mails sent, since there was no mail configured.
    $this->assertEmpty($this->getMails(['key' => 'abandoned_cart']), 'No emails have been sent.');
  }

  /**
   * Tests if the mail gets sent in the site's language for anonymous customers.
   */
  public function testMailLanguageAnonymousCustomer() {
    // Set site language to French.
    ConfigurableLanguage::createFromLangcode('fr')->save();
    $this->config('system.site')
      ->set('default_langcode', 'fr')
      ->save();

    $this->drupalLogout();
    $this->addProductToCart($this->product);

    // Checkout as guest and stop at review step.
    $this->goToCheckout();
    $this->assertCheckoutProgressStep('Login');
    $this->submitForm([], 'Continue as Guest');
    $this->processOrderInformation();
    $this->assertCheckoutProgressStep('Review');

    // Manually set the order's last update date to one day ago.
    $order = Order::load(1);
    $this->setOrderChangedDate(1, $order->changed->value - static::SECONDS_IN_DAY);

    // Run cron.
    $this->cronRun();

    // Assert that the customer received an email in French.
    $mails = $this->getMails(['key' => 'abandoned_cart']);
    $this->assertEquals('fr', $mails[0]['langcode']);
  }

  /**
   * Tests if the mail gets sent user's prefered language.
   *
   * The mail can only get sent in the user's prefered language if the user is
   * authenticated.
   */
  public function testMailLanguageAuthenticatedCustomer() {
    // Install languages French and Dutch.
    foreach (['fr', 'nl'] as $langcode) {
      ConfigurableLanguage::createFromLangcode($langcode)->save();
    }

    // Set site language to French.
    $this->config('system.site')
      ->set('default_langcode', 'fr')
      ->save();

    $this->drupalLogout();
    $account = $this->drupalCreateUser(['access content'], 'customer');

    // Set customer's language to Dutch.
    $account->preferred_langcode->value = 'nl';
    $account->save();

    $this->drupalLogin($account);
    $this->addProductToCart($this->product);
    $this->goToCheckout();

    // Manually set the order's last update date to one day ago.
    $order = Order::load(1);
    $this->setOrderChangedDate(1, $order->changed->value - static::SECONDS_IN_DAY);

    // Run cron.
    $this->cronRun();

    // Assert that the customer received an email in Dutch.
    $mails = $this->getMails(['key' => 'abandoned_cart']);
    $this->assertEquals('nl', $mails[0]['langcode']);
  }

}
