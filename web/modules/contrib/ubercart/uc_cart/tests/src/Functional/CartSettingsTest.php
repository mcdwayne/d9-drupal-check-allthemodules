<?php

namespace Drupal\Tests\uc_cart\Functional;

use Drupal\Core\Url;
use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Tests the cart settings page.
 *
 * @group ubercart
 */
class CartSettingsTest extends UbercartBrowserTestBase {

  public static $modules = ['uc_cart', 'block'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need system_breadcrumb_block because we test breadcrumbs.
    $this->drupalPlaceBlock('system_breadcrumb_block');

    // Need page_title_block because we test page titles.
    $this->drupalPlaceBlock('page_title_block');
  }

  /**
   * Tests add-to-cart message.
   */
  public function testAddToCartMessage() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);

    $this->addToCart($this->product);
    $assert->pageTextContains($this->product->getTitle() . ' added to your shopping cart.');

    $this->drupalPostForm('cart', [], 'Remove');
    $this->drupalPostForm('admin/store/config/cart', ['uc_cart_add_item_msg' => FALSE], 'Save configuration');

    $this->addToCart($this->product);
    $assert->pageTextNotContains($this->product->getTitle() . ' added to your shopping cart.');
  }

  /**
   * Tests add-to-cart redirection.
   */
  public function testAddToCartRedirect() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/config/cart');
    $this->assertField(
      'uc_add_item_redirect',
      'Add to cart redirect field exists'
    );

    $redirect = 'admin/store';
    $this->drupalPostForm(
      'admin/store/config/cart',
      ['uc_add_item_redirect' => $redirect],
      'Save configuration'
    );

    $this->drupalPostForm('node/' . $this->product->id(), [], 'Add to cart');
    $url_pass = ($this->getUrl() == Url::fromUri('base:' . $redirect, ['absolute' => TRUE])->toString());
    $this->assertTrue(
      $url_pass,
      'Add to cart redirect takes user to the correct URL.'
    );
  }

  /**
   * Tests add-to-cart redirection with ?query string.
   */
  public function testAddToCartQueryRedirect() {
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm(
      'admin/store/config/cart',
      ['uc_add_item_redirect' => '<none>'],
      'Save configuration'
    );

    $this->drupalPostForm('node/' . $this->product->id(), [], 'Add to cart', ['query' => ['test' => 'querystring']]);
    $url = $this->product->toUrl('canonical', ['absolute' => TRUE, 'query' => ['test' => 'querystring']])->toString();
    $this->assertTrue($this->getUrl() == $url, 'Add to cart no-redirect preserves the query string.');
  }

  /**
   * Tests that "Empty cart" button on the cart page works.
   */
  public function testEmptyCart() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Test that the feature is not enabled by default.
    $this->drupalPostForm('node/' . $this->product->id(), [], 'Add to cart');
    $assert->responseNotContains('Empty cart');

    // Test the admin settings itself.
    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/config/cart');
    $this->assertField('uc_cart_empty_button', 'Empty cart button checkbox found.');
    $this->drupalPostForm(NULL, ['uc_cart_empty_button' => TRUE], 'Save configuration');

    // Test the feature itself.
    $this->drupalGet('cart');
    $this->drupalPostForm(NULL, [], 'Empty cart');
    $assert->pageTextContains('Are you sure you want to empty your shopping cart?');
    $this->drupalPostForm(NULL, [], 'Confirm');
    $assert->pageTextContains('There are no products in your shopping cart.');
  }

  /**
   * Tests minimum subtotal for checkout.
   */
  public function testMinimumSubtotal() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/config/cart');
    $this->assertField(
      'uc_minimum_subtotal',
      'Minimum order subtotal field exists'
    );

    $minimum_subtotal = mt_rand(2, 9999);
    $this->drupalPostForm(
      NULL,
      ['uc_minimum_subtotal' => $minimum_subtotal],
      'Save configuration'
    );

    // Create two products, one below the minimum price and one above.
    $product_below_limit = $this->createProduct(['price' => $minimum_subtotal - 1]);
    $product_above_limit = $this->createProduct(['price' => $minimum_subtotal + 1]);
    $this->drupalLogout();

    // Checks if the lower-priced product triggers the minimum price logic.
    $this->drupalPostForm(
      'node/' . $product_below_limit->id(),
      [],
      'Add to cart'
    );
    $this->drupalPostForm('cart', [], 'Checkout');
    // Checks that checkout below the minimum order total was prevented.
    $assert->responseContains('The minimum order subtotal for checkout is');

    // Add another product to the cart and verify that we end up on the
    // checkout page.
    $this->drupalPostForm(
      'node/' . $product_above_limit->id(),
      [],
      'Add to cart'
    );
    $this->drupalPostForm('cart', [], 'Checkout');
    $assert->pageTextContains('Enter your billing address and information here.');
  }

  /**
   * Tests that continue shopping link returns customer to the correct place.
   */
  public function testContinueShopping() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Continue shopping link should take you back to the product page.
    $this->drupalPostForm('node/' . $this->product->id(), [], 'Add to cart');
    // Check that 'Continue shopping' link appears on the page.
    $assert->linkExists('Continue shopping');
    $links = $this->xpath('//a[@href="' . $this->product->toUrl()->toString() . '"]');
    $this->assertTrue(
      isset($links[0]),
      'Continue shopping link returns to the product page.'
    );

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/config/cart');
    $this->assertField(
      'uc_continue_shopping_type',
      'Continue shopping element display field exists'
    );
    $this->assertField(
      'uc_continue_shopping_url',
      'Default continue shopping link URL field exists'
    );

    // Test continue shopping button that sends users to a fixed URL.
    $settings = [
      'uc_continue_shopping_type' => 'button',
      'uc_continue_shopping_use_last_url' => FALSE,
      'uc_continue_shopping_url' => 'admin/store',
    ];
    $this->drupalPostForm(NULL, $settings, 'Save configuration');
    $this->drupalPostForm('cart', [], 'Continue shopping');
    $url_pass = ($this->getUrl() == Url::fromUri('base:' . $settings['uc_continue_shopping_url'], ['absolute' => TRUE])->toString());
    $this->assertTrue(
      $url_pass,
      'Continue shopping button takes the user to the correct URL.'
    );
  }

  /**
   * Tests the shopping cart page breadcrumb.
   */
  public function testCartBreadcrumb() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/config/cart');
    $this->assertField(
      'uc_cart_breadcrumb_text',
      'Custom cart breadcrumb text field exists'
    );
    $this->assertField(
      'uc_cart_breadcrumb_url',
      'Custom cart breadcrumb URL'
    );

    $settings = [
      'uc_cart_breadcrumb_text' => $this->randomMachineName(8),
      'uc_cart_breadcrumb_url' => $this->randomMachineName(8),
    ];
    $this->drupalPostForm(NULL, $settings, 'Save configuration');

    $this->drupalPostForm('node/' . $this->product->id(), [], 'Add to cart');
    // Test that the breadcrumb link text is set correctly.
    $assert->linkExists($settings['uc_cart_breadcrumb_text'], 0);
    $links = $this->xpath('//a[@href="' . Url::fromUri('internal:/' . $settings['uc_cart_breadcrumb_url'], ['absolute' => TRUE])->toString() . '"]');
    $this->assertTrue(
      isset($links[0]),
      'The breadcrumb link is set correctly.'
    );
  }

}
