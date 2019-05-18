<?php

namespace Drupal\Tests\uc_payment\Functional;

use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Tests the checkout payment pane.
 *
 * @group ubercart
 */
class PaymentPaneTest extends UbercartBrowserTestBase {

  public static $modules = ['uc_payment', 'uc_payment_pack'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->drupalLogin($this->adminUser);
    $this->addToCart($this->product);
  }

  /**
   * Verifies checkout page presents all enabled payment methods.
   */
  public function testPaymentMethodOptions() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // No payment methods.
    $this->drupalGet('cart/checkout');
    $assert->pageTextContains('Checkout cannot be completed without any payment methods enabled. Please contact an administrator to resolve the issue.');

    // Single payment method.
    $check = $this->createPaymentMethod('check');
    $this->drupalGet('cart/checkout');
    $assert->pageTextNotContains('Select a payment method from the following options.');
    $assert->assertEscaped($check['label']);
    $this->assertFieldByXPath("//input[@name='panes[payment][payment_method]' and @disabled='disabled']");

    // Multiple payment methods.
    $other = $this->createPaymentMethod('other');
    $this->drupalGet('cart/checkout');
    $assert->pageTextContains('Select a payment method from the following options.');
    $assert->assertEscaped($check['label']);
    $assert->assertEscaped($other['label']);
    $this->assertNoFieldByXPath("//input[@name='panes[payment][payment_method]' and @disabled='disabled']");
  }

  /**
   * Tests operation of the uc_payment_show_order_total_preview variable.
   */
  public function testOrderTotalPreview() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $edit = [
      'panes[payment][settings][show_preview]' => TRUE,
    ];
    $this->drupalPostForm('admin/store/config/checkout', $edit, 'Save configuration');
    $this->drupalGet('cart/checkout');
    $assert->pageTextContains('Order total:');

    $edit = [
      'panes[payment][settings][show_preview]' => FALSE,
    ];
    $this->drupalPostForm('admin/store/config/checkout', $edit, 'Save configuration');
    $this->drupalGet('cart/checkout');
    $assert->pageTextNotContains('Order total:');
  }

  /**
   * Tests free orders.
   */
  public function testFreeOrders() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $free_product = $this->createProduct(['price' => 0]);
    $check = $this->createPaymentMethod('check');

    // Check that paid products cannot be purchased for free.
    $this->drupalGet('cart/checkout');
    $assert->assertEscaped($check['label']);
    $assert->pageTextNotContains('No payment required');
    $assert->pageTextNotContains("Subtotal:\n      $0.00");

    // Check that a mixture of free and paid products
    // cannot be purchased for free.
    $this->addToCart($free_product);
    $this->drupalGet('cart/checkout');
    $assert->assertEscaped($check['label']);
    $assert->pageTextNotContains('No payment required');
    $assert->pageTextNotContains("Subtotal:\n      $0.00");

    // Check that free products can be purchased successfully with no payment.
    $this->drupalPostForm('cart', [], 'Remove');
    $this->drupalPostForm('cart', [], 'Remove');
    $this->addToCart($free_product);
    $this->drupalGet('cart/checkout');
    $assert->assertNoEscaped($check['label']);
    $assert->pageTextContains('No payment required');
    $assert->pageTextContains('Continue with checkout to complete your order.');
    $assert->pageTextMatches('/Subtotal:\s*\$0.00/', '"Subtotal: $0.00" found');

    // Check that this is the only available payment method.
    $assert->pageTextNotContains('Select a payment method from the following options.');
    $this->assertFieldByXPath("//input[@name='panes[payment][payment_method]' and @disabled='disabled']");
  }

}
