<?php

namespace Drupal\Tests\bigcommerce\Functional;

use Drupal\Core\Url;
use Drupal\migrate\MigrateMessageInterface;
use Drupal\Tests\commerce\Functional\CommerceBrowserTestBase;
use Drupal\commerce_order\Entity\Order;
use Drupal\Tests\commerce_cart\Traits\CartBrowserTestTrait;

/**
 * Tests the cart functionality of the BigCommerce module.
 *
 * @group bigcommerce
 */
class CartTest extends CommerceBrowserTestBase implements MigrateMessageInterface {
  use BigCommerceTestTrait;
  use CartBrowserTestTrait;

  /**
   * The cart order to test against.
   *
   * @var \Drupal\commerce_order\Entity\OrderInterface
   */
  protected $cart;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'bigcommerce',
    'bigcommerce_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->cart = $this->container->get('commerce_cart.cart_provider')->createCart('default');

    // Configure BigCommerce to use the stub.
    $config = $this->config('bigcommerce.settings');
    $config->set('api', [
      'path' => Url::fromUri('base://bigcommerce_stub/cart')->setAbsolute()->toString(),
      'access_token' => 'an access token',
      'client_id' => 'a client ID',
      'client_secret' => 'a client secret',
      'timeout' => 15,
    ]);
    $config->save();
    $this->executeMigrations();
    $this->assertMigrations();
  }

  /**
   * Test cart.
   */
  public function testCart() {
    $this->drupalGet('product/1/variations');
    $products = \Drupal::entityTypeManager()
      ->getStorage('commerce_product')
      ->loadByProperties(['title' => '[Sample] Smith Journal 13']);
    $product = reset($products);
    $variations = $product->getVariations();
    $variation = reset($variations);
    // Confirm that the initial add to cart submit works.
    $this->postAddToCart($product);

    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertOrderItemInOrder($variation, $order_items[0]);
    $this->assertEquals($this->cart->getData('bigcommerce_cart_id'), 'bc218c65-7a32-4ab7-8082-68730c074d02');
    $this->assertEquals($order_items[0]->getData('bigcommerce_item_id'), '6e193ce6-f327-4dcc-b75e-72cf6738525e');

    // Confirm that the second add to cart submit increments the quantity
    // of the first order item.
    $this->postAddToCart($product);
    \Drupal::entityTypeManager()->getStorage('commerce_order')->resetCache();
    \Drupal::entityTypeManager()->getStorage('commerce_order_item')->resetCache();
    $this->cart = Order::load($this->cart->id());
    $order_items = $this->cart->getItems();
    $this->assertNotEmpty(count($order_items) == 1, 'No additional order items were created');

    $this->assertOrderItemInOrder($variation, $order_items[0], 2);

    $this->drupalGet('cart');
    $this->assertSession()->fieldExists('edit_quantity[0]');
    $this->getSession()->getPage()->fillField('edit_quantity[0]', 5);
    $this->submitForm([], t('Update cart'));
    $this->assertSession()->fieldValueEquals('edit_quantity[0]', 5);

    $this->assertSession()->buttonExists('Remove');
    $this->submitForm([], t('Remove'));
    $this->assertSession()->fieldNotExists('edit_quantity[0]');
  }

}
