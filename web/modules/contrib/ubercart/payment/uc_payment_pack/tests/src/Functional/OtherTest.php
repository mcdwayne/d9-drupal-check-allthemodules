<?php

namespace Drupal\Tests\uc_payment_pack\Functional;

use Drupal\uc_order\Entity\Order;

/**
 * Tests the payment method pack Other payment method.
 *
 * @group ubercart
 */
class OtherTest extends PaymentPackTestBase {

  /**
   * Tests for Other payment method.
   */
  public function testOther() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $other = $this->createPaymentMethod('other');

    // Test checkout page.
    $this->drupalGet('cart/checkout');
    $this->assertFieldByName('panes[payment][payment_method]', $other['id'], 'Other payment method is selected at checkout.');

    // Test review order page.
    $this->drupalPostForm(NULL, [], 'Review order');
    // Check that the Other payment method was found on the review page.
    $assert->pageTextContains('Other');
    $this->drupalPostForm(NULL, [], 'Submit order');

    // Test user order view.
    $order = Order::load(1);
    $this->assertEquals($order->getPaymentMethodId(), $other['id'], 'Order has other payment method.');

    $this->drupalGet('user/' . $order->getOwnerId() . '/orders/' . $order->id());
    // Check that the Other payment method was found on the user order page.
    $assert->pageTextContains('Method: Other');

    // Test admin order view.
    $this->drupalGet('admin/store/orders/' . $order->id());
    // Check that the Other payment method was found on the admin order page.
    $assert->pageTextContains('Method: Other');

    $this->drupalGet('admin/store/orders/' . $order->id() . '/edit');
    $this->assertFieldByName('payment_method', $other['id'], 'Other payment method is selected in the order edit form.');
    $edit = ['payment_details[description]' => $this->randomString()];
    $this->drupalPostForm(NULL, [], 'Save changes');
    // @todo Test storage of payment details.
  }

}
