<?php

namespace Drupal\Tests\uc_payment_pack\Functional;

use Drupal\uc_order\Entity\Order;

/**
 * Tests the payment method pack CashOnDelivery payment method.
 *
 * @group ubercart
 */
class CashOnDeliveryTest extends PaymentPackTestBase {

  /**
   * Tests for CashOnDelivery payment method.
   */
  public function testCashOnDelivery() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalGet('admin/store/config/payment/add/cod');
    $this->assertFieldByName('settings[policy]', 'Full payment is expected upon delivery or prior to pick-up.', 'Default COD policy found.');

    $cod = $this->createPaymentMethod('cod', [
      'settings[policy]' => $this->randomString(),
    ]);
    // @todo Test enabling delivery date on settings page.

    // Test checkout page.
    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', $cod['id'], 'COD payment method is selected at checkout.');
    $assert->assertEscaped($cod['settings[policy]'], 'COD policy found at checkout.');

    // Test review order page.
    $this->drupalPostForm(NULL, [], 'Review order');
    // Check that COD payment method was found on the review order page.
    $assert->pageTextContains('Cash on delivery');
    $this->drupalPostForm(NULL, [], 'Submit order');

    // Test user order view.
    $order = Order::load(1);
    $this->assertEquals($order->getPaymentMethodId(), $cod['id'], 'Order has COD payment method.');

    $this->drupalGet('user/' . $order->getOwnerId() . '/orders/' . $order->id());
    // Check that COD payment method is displayed on user orders page.
    $assert->pageTextContains('Method: Cash on delivery');

    // Test admin order view.
    $this->drupalGet('admin/store/orders/' . $order->id());
    // Check that COD payment method is displayed on admin orders page.
    $assert->pageTextContains('Method: Cash on delivery');
  }

}
