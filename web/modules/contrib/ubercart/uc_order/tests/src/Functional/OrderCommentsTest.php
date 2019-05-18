<?php

namespace Drupal\Tests\uc_order\Functional;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Xss;
use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Tests customer administration page functionality.
 *
 * @group ubercart
 */
class OrderCommentsTest extends UbercartBrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = ['uc_order', 'views'];

  /**
   * The user who placed the order.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $customer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->customer = $this->drupalCreateUser(['view own orders']);
  }

  /**
   * Tests adding admin comments on administrator's order view page.
   */
  public function testAdminViewAddComment() {
    $this->drupalLogin($this->adminUser);
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Create an order to test order events.
    $order = $this->createOrder();

    // Check that order creation was entered as a comment.
    $this->drupalGet('admin/store/orders/' . $order->id());
    $assert->pageTextContains('This order has no comments associated with it.');
    $assert->pageTextContains('This order has no admin comments associated with it.');

    // Changing the order status on the admin form will also create
    // an order comment.
    $edit = ['status' => 'processing'];
    $this->drupalPostForm('admin/store/orders/' . $order->id(), $edit, 'Update');
    $assert->pageTextContains('Order updated.');
    // Check for new priority in the comments section.
    // @todo Should use xpath to make sure we are checking this in the order
    // comments pane, not just finding this somewhere on the page.
    $assert->responseContains('<td class="status priority-low">Processing</td>');

    // Add an order comment from the order view page.
    $edit = ['order_comment' => ($message = $this->randomString(30))];
    $this->drupalPostForm('admin/store/orders/' . $order->id(), $edit, 'Update');
    $assert->pageTextContains('Order updated.');
    // Check for new properly-escaped comment in the comments section.
    // Xss::filter() is used because that is what #markup does to the message
    // text and we want to ensure we are comparing apples to apples.
    // @todo Should use xpath to make sure we are checking this in the order
    // comments pane, not just finding this somewhere on the page.
    $assert->responseContains('<td class="message">' . Xss::filterAdmin($message) . '</td>');

    // Add an admin order comment from the order view page.
    $edit = ['admin_comment' => ($message = $this->randomString(30))];
    $this->drupalPostForm('admin/store/orders/' . $order->id(), $edit, 'Update');
    $assert->pageTextContains('Order updated.');
    // Check for new properly-escaped comment in the comments section.
    // @todo Should use xpath to make sure we are checking this in the order
    // comments pane, not just finding this somewhere on the page.
    $assert->responseContains('<td class="message">' . Xss::filterAdmin($message) . '</td>');
  }

  /**
   * Tests adding admin comments on administrator's order edit page.
   */
  public function testAdminEditAddComment() {
    $this->drupalLogin($this->adminUser);
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Create an order to test order events.
    $order = $this->createOrder();

    // Check that the edit page is viewable and has the expected comment text.
    $this->drupalGet('admin/store/orders/' . $order->id() . '/edit');
    $assert->pageTextContains('Admin comments:');
    $assert->pageTextContains('No admin comments have been entered for this order.');

    // Add an admin order comment from the order view page.
    $edit = ['admin_comment' => ($message = $this->randomString(30))];
    $this->drupalPostForm('admin/store/orders/' . $order->id() . '/edit', $edit, 'Save changes');
    $assert->pageTextContains('Order changes saved.');
    // Check for new properly-escaped comment in the comments section.
    // Html::decodeEntities(Xss::filter()) is used because Xss::filter()
    // HTML-encodes the string, but we want to compare to the text visible
    // in the browser, which doesn't show the encoding.
    // @todo Should use xpath to make sure we are checking this in the order
    // comments pane, not just finding this somewhere on the page.
    $assert->pageTextContains('[' . $this->adminUser->getDisplayName() . '] ' . Html::decodeEntities(Xss::filter($message, [])));
  }

  /**
   * Tests adding admin comments on administrator's order view page.
   *
   * Test as an authenticated user.
   */
  public function testUserCheckoutAddComment() {
    $this->drupalLogin($this->customer);
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Add product to cart.
    $this->addToCart($this->product);

    // Start checkout.
    $this->drupalPostForm(NULL, [], 'Checkout');

    // Fill in checkout form, add a comment.
    $edit = $this->populateCheckoutForm([
      'panes[comments][comments]' => ($message = $this->randomString(30)),
    ]);

    // Review order and make sure entered comment shows up.
    $this->drupalPostForm(NULL, $edit, 'Review order');
    $assert->pageTextContains('Order comments');
    $assert->pageTextContains('Comment:');
    $assert->responseContains(Xss::filterAdmin($message));

    // Submit order.
    $this->drupalPostForm(NULL, [], 'Submit order');

    // Go to user view order page and make sure entered comment shows up.
    $this->drupalGet('user/' . $this->customer->id() . '/orders');
    $this->clickLink('View');
    $assert->pageTextContains('Order comments:');
    $assert->pageTextContains('Order created.');
    $assert->pageTextContains('Pending');
    $assert->responseContains(Xss::filterAdmin($message));
  }

}
