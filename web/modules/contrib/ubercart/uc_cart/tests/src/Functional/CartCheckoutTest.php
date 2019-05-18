<?php

namespace Drupal\Tests\uc_cart\Functional;

use Drupal\uc_cart\CartInterface;
use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Tests the cart and checkout functionality.
 *
 * @group ubercart
 */
class CartCheckoutTest extends UbercartBrowserTestBase {

  public static $modules = ['uc_payment', 'uc_payment_pack'];

  /**
   * Authenticated but unprivileged user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $customer;

  /**
   * The cart manager.
   *
   * @var \Drupal\uc_cart\CartManagerInterface
   */
  protected $cartManager;

  /**
   * The test user's cart.
   *
   * @var \Drupal\uc_cart\CartInterface
   */
  protected $cart;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need page_title_block because we test page titles.
    $this->drupalPlaceBlock('page_title_block');

    // Get a reference to the cart.
    $this->cartManager = \Drupal::service('uc_cart.manager');
    $this->cart = $this->cartManager->get();

    // Create a simple customer user account.
    $this->customer = $this->drupalCreateUser();

    // Create a payment method.
    $this->createPaymentMethod('check');

    // Ensure test mails are logged.
    \Drupal::configFactory()->getEditable('system.mail')
      ->set('interface.uc_order', 'test_mail_collector')
      ->save();
  }

  /**
   * Tests cart API.
   */
  public function testCartApi() {
    // Test the empty cart.
    $items = $this->cart->getContents();
    $this->assertEquals($items, [], 'Cart is an empty array.');

    // Add an item to the cart.
    $this->cart->addItem($this->product->id());

    $items = $this->cart->getContents();
    $this->assertEquals(count($items), 1, 'Cart contains one item.');
    $item = reset($items);
    $this->assertEquals($item->nid->target_id, $this->product->id(), 'Cart item nid is correct.');
    $this->assertEquals($item->qty->value, 1, 'Cart item quantity is correct.');

    // Add more of the same item.
    $qty = mt_rand(1, 100);
    $this->cart->addItem($this->product->id(), $qty);

    $items = $this->cart->getContents();
    $this->assertEquals(count($items), 1, 'Updated cart contains one item.');
    $item = reset($items);
    $this->assertEquals($item->qty->value, $qty + 1, 'Updated cart item quantity is correct.');

    // Set the quantity and data.
    $qty = mt_rand(1, 100);
    $item->qty->value = $qty;
    $item->data->updated = TRUE;
    $item->save();

    $items = $this->cart->getContents();
    $item = reset($items);
    $this->assertEquals($item->qty->value, $qty, 'Set cart item quantity is correct.');
    $this->assertTrue($item->data->updated, 'Set cart item data is correct.');

    // Add an item with different data to the cart.
    $this->cart->addItem($this->product->id(), 1, ['test' => TRUE]);

    $items = $this->cart->getContents();
    $this->assertEquals(count($items), 2, 'Updated cart contains two items.');

    // Remove the items.
    foreach ($items as $item) {
      $item->delete();
    }

    $items = $this->cart->getContents();
    $this->assertEquals(count($items), 0, 'Cart is empty after removal.');

    // Empty the cart.
    $this->cart->addItem($this->product->id());
    $this->cart->emptyCart();

    $items = $this->cart->getContents();
    $this->assertEquals($items, [], 'Cart is emptied correctly.');
  }

  /**
   * Tests basic cart page functionality.
   */
  public function testCartPage() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    \Drupal::service('module_installer')->install(['uc_cart_entity_test'], FALSE);

    // Test the empty cart.
    $this->drupalGet('cart');
    $assert->pageTextContains('There are no products in your shopping cart.');

    // Add an item to the cart.
    $this->addToCart($this->product);
    $assert->pageTextContains($this->product->label() . ' added to your shopping cart.');
    $assert->pageTextContains('hook_uc_cart_item_insert fired');

    // Test that the item shows up in the cart.
    $this->drupalGet('cart');
    $assert->pageTextContains($this->product->label(), 'The product is in the cart.');
    $this->assertFieldByName('items[0][qty]', 1, 'The product quantity is 1.');

    // Add the item again.
    $this->addToCart($this->product);
    $assert->pageTextContains('Your item(s) have been updated.');
    $assert->pageTextContains('hook_uc_cart_item_update fired');

    // Test that there are now two of the item in the cart.
    $this->drupalGet('cart');
    $this->assertFieldByName('items[0][qty]', 2, 'The product quantity is 2.');

    // Update the quantity.
    $qty = mt_rand(3, 100);
    $this->drupalPostForm('cart', ['items[0][qty]' => $qty], 'Update cart');
    $assert->pageTextContains('Your cart has been updated.');
    $this->assertFieldByName('items[0][qty]', $qty, 'The product quantity was updated.');
    $assert->pageTextContains('hook_uc_cart_item_update fired');

    // Update the quantity to zero.
    $this->drupalPostForm('cart', ['items[0][qty]' => 0], 'Update cart');
    $assert->pageTextContains('Your cart has been updated.');
    $assert->pageTextContains('There are no products in your shopping cart.');
    $assert->pageTextContains('hook_uc_cart_item_delete fired');

    // Test the remove item button.
    $this->addToCart($this->product);
    $this->drupalPostForm('cart', [], 'Remove');
    $assert->pageTextContains($this->product->label() . ' removed from your shopping cart.');
    $assert->pageTextContains('There are no products in your shopping cart.');
    $assert->pageTextContains('hook_uc_cart_item_delete fired');

    // Test the empty cart button.
    $this->addToCart($this->product);
    $this->drupalGet('cart');
    // Test that the empty cart button is not shown by default.
    $assert->pageTextNotContains('Empty cart');
    // Enable the empty cart button.
    \Drupal::configFactory()->getEditable('uc_cart.settings')->set('empty_button', TRUE)->save();
    $this->drupalPostForm('cart', [], 'Empty cart');
    // Verify that we get a confirmation page.
    $assert->pageTextContains('Are you sure you want to empty your shopping cart?');
    $this->drupalPostForm(NULL, [], 'Confirm');
    // Verify that the cart is now empty.
    $assert->pageTextContains('There are no products in your shopping cart.');
    $assert->pageTextContains('hook_uc_cart_item_delete fired');
  }

  /**
   * Tests that anonymous cart is merged into authenticated cart upon login.
   */
  public function testCartMerge() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Add an item to the cart as an anonymous user.
    $this->drupalLogin($this->customer);
    $this->addToCart($this->product);
    $assert->pageTextContains($this->product->label() . ' added to your shopping cart.');
    $this->drupalLogout();

    // Add an item to the cart as an anonymous user.
    $this->addToCart($this->product);
    $assert->pageTextContains($this->product->label() . ' added to your shopping cart.');

    // Log in and check the items are merged.
    $this->drupalLogin($this->customer);
    $this->drupalGet('cart');
    $assert->pageTextContains($this->product->label(), 'The product remains in the cart after logging in.');
    $this->assertFieldByName('items[0][qty]', 2, 'The product quantity is 2.');
  }

  /**
   * Tests that cart automatically removes products that have been deleted.
   */
  public function testDeletedCartItem() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Add a product to the cart, then delete the node.
    $this->addToCart($this->product);
    $this->product->delete();

    // Test that the cart is empty.
    $this->drupalGet('cart');
    $assert->pageTextContains('There are no products in your shopping cart.');
    $this->assertSame([], $this->cart->getContents(), 'There are no items in the cart.');
  }

  /**
   * Tests cart pane on checkout page.
   */
  public function testCheckoutCartPane() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Add a product to the cart.
    $this->addToCart($this->product);
    $this->drupalGet('cart');
    $this->assertFieldByName('items[0][qty]', 1, 'The product quantity is 1.');

    // Test the checkout pane.
    $this->drupalPostForm(NULL, [], 'Checkout');
    $assert->pageTextContains($this->product->label(), 'The product title is displayed.');
    $assert->pageTextContains('1 ×', 'The product quantity is displayed.');
    $assert->pageTextContains(uc_currency_format($this->product->price->value), 'The product price is displayed.');

    // Change the quantity.
    $qty = mt_rand(3, 100);
    $this->drupalPostForm('cart', ['items[0][qty]' => $qty], 'Checkout');

    // Test the checkout pane.
    $assert->pageTextContains($this->product->label(), 'The product title is displayed.');
    $assert->pageTextContains($qty . ' ×', 'The updated product quantity is displayed.');
    $assert->pageTextContains(uc_currency_format($qty * $this->product->price->value), 'The updated product price is displayed.');
  }

  /**
   * Tests Rule integration for uc_cart_maximum_product_qty reaction rule.
   */
  // public function testMaximumQuantityRule() {
  //   /** @var \Drupal\Tests\WebAssert $assert */
  //   $assert = $this->assertSession();
  //
  //   // Enable the example maximum quantity rule.
  //   $rule = rules_config_load('uc_cart_maximum_product_qty');
  //   $rule->active = TRUE;
  //   $rule->save();

  //   // Try to add more items than allowed to the cart.
  //   $this->addToCart($this->product);
  //   $this->drupalPostForm('cart', ['items[0][qty]' => 11], 'Update cart');

  //   // Test the restriction was applied.
  //   $assert->pageTextContains('You are only allowed to order a maximum of 10 of '. $this->product->label());
  //   $this->assertFieldByName('items[0][qty]', 10);
  // }

  /**
   * Tests authenticated user checkout.
   */
  public function testAuthenticatedCheckout() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->drupalLogin($this->customer);
    $this->addToCart($this->product);
    $order = $this->checkout();
    $assert->responseContains('Your order is complete!');
    $assert->responseContains('While logged in');
    $this->assertEquals($order->getOwnerId(), $this->customer->id(), 'Order has the correct user ID.');
    $this->assertEquals($order->getEmail(), $this->customer->getEmail(), 'Order has the correct email address.');

    // Check that cart is now empty.
    $this->drupalGet('cart');
    $assert->pageTextContains('There are no products in your shopping cart.');
  }

  /**
   * Tests generating a user account upon anonymous checkout.
   */
  public function testAnonymousCheckoutAccountGenerated() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->addToCart($this->product);
    $this->checkout();
    $assert->responseContains('Your order is complete!');

    // Test new account email.
    $mails = $this->getMails(['id' => 'user_register_no_approval_required']);
    $mail = array_pop($mails);
    $account = $mail['params']['account'];
    $this->assertTrue(!empty($account->name->value), 'New username is not empty.');
    $this->assertTrue(!empty($account->password), 'New password is not empty.');
    $this->assertTrue(strpos($mail['body'], $account->name->value) !== FALSE, 'Mail body contains username.');

    // Test invoice email.
    $mails = $this->getMails(['subject' => 'Your Order at Ubercart']);
    $mail = array_pop($mails);
    $this->assertTrue(strpos($mail['body'], $account->name->value) !== FALSE, 'Invoice body contains username.');
    $this->assertTrue(strpos($mail['body'], $account->password) !== FALSE, 'Invoice body contains password.');

    // We can check the password now we know it.
    $assert->pageTextContains($account->name->value, 'Username is shown on screen.');
    $assert->pageTextContains($account->password, 'Password is shown on screen.');

    // Check that cart is now empty.
    $this->drupalGet('cart');
    $assert->pageTextContains('There are no products in your shopping cart.');

    // Check that the password works.
    $edit = [
      'name' => $account->name->value,
      'pass' => $account->password,
    ];
    $this->drupalPostForm('user', $edit, 'Log in');
  }

  /**
   * Tests anonymous checkout with an existing account.
   */
  public function testAnonymousCheckoutAccountProvided() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $settings = [
      // Allow customer to specify username and password.
      'uc_cart_new_account_name' => TRUE,
      'uc_cart_new_account_password' => TRUE,
    ];
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/store/config/checkout', $settings, 'Save configuration');
    $this->drupalLogout();

    $username = $this->randomMachineName(20);
    $password = $this->randomMachineName(20);

    $this->addToCart($this->product);
    $this->checkout([
      'panes[customer][new_account][name]' => $username,
      'panes[customer][new_account][pass]' => $password,
      'panes[customer][new_account][pass_confirm]' => $password,
    ]);
    $assert->responseContains('Your order is complete!');
    $assert->pageTextContains($username, 'Username is shown on screen.');
    $assert->pageTextNotContains($password, 'Password is not shown on screen.');

    // Test new account email.
    $mails = $this->getMails(['id' => 'user_register_no_approval_required']);
    $mail = array_pop($mails);
    $this->assertTrue(strpos($mail['body'], $username) !== FALSE, 'Mail body contains username.');

    // Test invoice email.
    $mails = $this->getMails(['subject' => 'Your Order at Ubercart']);
    $mail = array_pop($mails);
    $this->assertTrue(strpos($mail['body'], $username) !== FALSE, 'Invoice body contains username.');
    $this->assertFalse(strpos($mail['body'], $password) !== FALSE, 'Invoice body does not contain password.');

    // Check that cart is now empty.
    $this->drupalGet('cart');
    $assert->pageTextContains('There are no products in your shopping cart.');

    // Check that the password works.
    $edit = [
      'name' => $username,
      'pass' => $password,
    ];
    $this->drupalPostForm('user', $edit, 'Log in');
  }

  /**
   * Tests associating an anonymous order with an existing account.
   */
  public function testAnonymousCheckoutAccountExists() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->addToCart($this->product);
    $this->checkout(['panes[customer][primary_email]' => $this->customer->getEmail()]);
    $assert->responseContains('Your order is complete!');
    $assert->responseContains('order has been attached to the account we found');

    // Check that cart is now empty.
    $this->drupalGet('cart');
    $assert->pageTextContains('There are no products in your shopping cart.');
  }

  /**
   * Tests generating a new account at checkout.
   */
  public function testCheckoutNewUsername() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Configure the checkout for this test.
    $this->drupalLogin($this->adminUser);
    $settings = [
      // Allow customer to specify username.
      'uc_cart_new_account_name' => TRUE,
      // Disable address panes.
      'panes[delivery][status]' => FALSE,
      'panes[billing][status]' => FALSE,
    ];
    $this->drupalPostForm('admin/store/config/checkout', $settings, 'Save configuration');
    $this->drupalLogout();

    // Test with an account that already exists.
    $this->addToCart($this->product);
    $edit = [
      'panes[customer][primary_email]' => $this->randomMachineName(8) . '@example.com',
      'panes[customer][new_account][name]' => $this->adminUser->name->value,
    ];
    $this->drupalPostForm('cart/checkout', $edit, 'Review order');
    $assert->pageTextContains('The username ' . $this->adminUser->name->value . ' is already taken.');

    // Let the account be automatically created instead.
    $edit = [
      'panes[customer][primary_email]' => $this->randomMachineName(8) . '@example.com',
      'panes[customer][new_account][name]' => '',
    ];
    $this->drupalPostForm('cart/checkout', $edit, 'Review order');
    $this->drupalPostForm(NULL, [], 'Submit order');
    $assert->pageTextContains('Your order is complete!');
    $assert->pageTextContains('A new account has been created');
  }

  /**
   * Tests blocked user checkout.
   */
  public function testCheckoutBlockedUser() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Block user after checkout.
    $settings = [
      'uc_new_customer_status_active' => FALSE,
    ];
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/store/config/checkout', $settings, 'Save configuration');
    $this->drupalLogout();

    // Test as anonymous user.
    $this->addToCart($this->product);
    $this->checkout();
    $assert->responseContains('Your order is complete!');

    // Test new account email.
    $mails = $this->getMails(['id' => 'user_register_pending_approval']);
    $this->assertTrue(!empty($mails), 'Blocked user email found.');
    $mails = $this->getMails(['id' => 'user_register_no_approval_required']);
    $this->assertTrue(empty($mails), 'No unblocked user email found.');
  }

  /**
   * Tests logging in the customer after checkout.
   */
  public function testCheckoutLogin() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Log in after checkout.
    $settings = [
      'uc_new_customer_login' => TRUE,
    ];
    $this->drupalLogin($this->adminUser);
    $this->drupalPostForm('admin/store/config/checkout', $settings, 'Save configuration');
    $this->drupalLogout();

    // Test checkout.
    $this->addToCart($this->product);
    $this->checkout();
    $assert->responseContains('Your order is complete!');
    $assert->responseContains('you are already logged in');

    // Confirm login.
    $this->drupalGet('<front>');
    $assert->pageTextContains('Member for ', 'User is logged in.');

    // Check that cart is now empty.
    $this->drupalGet('cart');
    $assert->pageTextContains('There are no products in your shopping cart.');
  }

  /**
   * Tests checkout complete functioning.
   */
  public function testCheckoutComplete() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Payment notification is received first.
    $order_data = [
      'uid' => 0,
      'primary_email' => 'simpletest@example.com',
    ];
    $order = $this->createOrder($order_data);
    uc_payment_enter($order->id(), 'other', $order->getTotal());
    $output = $this->cartManager->completeSale($order);

    // Check that a new account was created.
    $this->assertTrue(strpos($output['#message']['#markup'], 'new account has been created') !== FALSE, 'Checkout message mentions new account.');

    // 3 e-mails: new account, customer invoice, admin invoice.
    $this->assertMailString('subject', 'Account details', 3, 'New account email was sent');
    $this->assertMailString('subject', 'Your Order at Ubercart', 3, 'Customer invoice was sent');
    $this->assertMailString('subject', 'New Order at Ubercart', 3, 'Admin notification was sent');

    $mails = $this->getMails();
    $password = $mails[0]['params']['account']->password;
    $this->assertTrue(!empty($password), 'New password is not empty.');

    // Clear contents of mail collector.
    \Drupal::state()->set('system.test_mail_collector', []);

    // Different user, sees the checkout page first.
    $order_data = [
      'uid' => 0,
      'primary_email' => 'simpletest2@example.com',
    ];
    $order = $this->createOrder($order_data);
    $output = $this->cartManager->completeSale($order);
    uc_payment_enter($order->id(), 'other', $order->getTotal());

    // 3 e-mails: new account, customer invoice, admin invoice.
    $this->assertMailString('subject', 'Account details', 3, 'New account email was sent');
    $this->assertMailString('subject', 'Your Order at Ubercart', 3, 'Customer invoice was sent');
    $this->assertMailString('subject', 'New Order at Ubercart', 3, 'Admin notification was sent');

    $mails = $this->getMails();
    $password = $mails[0]['params']['account']->password;
    $this->assertTrue(!empty($password), 'New password is not empty.');

    // Clear contents of mail collector.
    \Drupal::state()->set('system.test_mail_collector', []);

    // Same user, new order.
    $order = $this->createOrder($order_data);
    $output = $this->cartManager->completeSale($order);
    uc_payment_enter($order->id(), 'other', $order->getTotal());

    // Check that no new account was created.
    $this->assertTrue(strpos($output['#message']['#markup'], 'order has been attached to the account') !== FALSE, 'Checkout message mentions existing account.');

    // 2 e-mails: customer invoice, admin invoice.
    $this->assertNoMailString('subject', 'Account details', 3, 'New account email was sent');
    $this->assertMailString('subject', 'Your Order at Ubercart', 3, 'Customer invoice was sent');
    $this->assertMailString('subject', 'New Order at Ubercart', 3, 'Admin notification was sent');
  }

  /**
   * Tests that cart orders are marked abandoned after a timeout.
   */
  public function testCartOrderTimeout() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $this->addToCart($this->product);
    $this->drupalPostForm('cart', [], 'Checkout');
    $assert->pageTextContains(
      'Enter your billing address and information here.',
      'Viewed cart page: Billing pane has been displayed.'
    );

    // Submit the checkout page.
    $edit = $this->populateCheckoutForm();
    $oldname = $edit['panes[delivery][first_name]'];
    $this->drupalPostForm('cart/checkout', $edit, 'Review order');

    $order_ids = \Drupal::entityQuery('uc_order')
      ->condition('delivery_first_name', $oldname)
      ->execute();
    $order_id = reset($order_ids);
    if ($order_id) {
      // Go to a different page, then back to order to make sure
      // order_id is the same.
      $this->drupalGet('<front>');
      $this->addToCart($this->product);
      $this->drupalPostForm('cart', [], 'Checkout');
      // Check that customer name was unchanged.
      $assert->responseContains($oldname);
      $this->drupalPostForm('cart/checkout', $edit, 'Review order');
      $new_order_ids = \Drupal::entityQuery('uc_order')
        ->condition('delivery_first_name', $edit['panes[delivery][first_name]'])
        ->execute();
      $new_order_id = reset($new_order_ids);
      $this->assertEquals($order_id, $new_order_id, 'Original order_id was reused.');

      // Jump 10 minutes into the future.
      // @todo Can we set changed through the Entity API rather than DBTNG?
      db_update('uc_orders')
        ->fields(['changed' => \Drupal::time()->getCurrentTime() - CartInterface::ORDER_TIMEOUT - 1])
        ->condition('order_id', $order_id)
        ->execute();

      // Go to a different page, then back to order to verify that we are
      // using a new order.
      $this->drupalGet('<front>');
      $this->drupalPostForm('cart', [], 'Checkout');
      // Check that customer name was cleared after timeout.
      $assert->responseNotContains($oldname);
      $newname = $this->randomMachineName(10);
      $edit['panes[delivery][first_name]'] = $newname;
      $this->drupalPostForm('cart/checkout', $edit, 'Review order');

      $new_order_ids = \Drupal::entityQuery('uc_order')
        ->condition('delivery_first_name', $newname)
        ->execute();
      $new_order_id = reset($new_order_ids);
      $this->assertNotEquals($order_id, $new_order_id, 'New order was created after timeout.');

      // Force the order to load from the DB instead of the entity cache.
      $old_order = \Drupal::entityTypeManager()->getStorage('uc_order')->loadUnchanged($order_id);
      // Verify that the status of old order is abandoned.
      $this->assertEquals($old_order->getStatusId(), 'abandoned', 'Original order was marked abandoned.');
    }
    else {
      $this->fail('No order was created.');
    }
  }

  /**
   * Tests functioning of customer information pane on checkout page.
   */
  public function testCustomerInformationCheckoutPane() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    // Log in as a customer and add an item to the cart.
    $this->drupalLogin($this->customer);
    $this->addToCart($this->product);
    $this->drupalPostForm('cart', [], 'Checkout');

    // Test the customer information pane.
    $mail = $this->customer->getEmail();
    $assert->pageTextContains('Customer information');
    $assert->pageTextContains('Order information will be sent to your account e-mail listed below.');
    $assert->pageTextContains('E-mail address: ' . $mail);

    // Use the 'edit' link to change the email address on the account.
    $new_mail = $this->randomMachineName() . '@example.com';
    $this->clickLink('edit');
    $data = [
      'current_pass' => $this->customer->pass_raw,
      'mail' => $new_mail,
    ];
    $this->drupalPostForm(NULL, $data, 'Save');

    // Test the updated email address.
    $assert->pageTextContains('Order information will be sent to your account e-mail listed below.');
    $assert->pageTextNotContains('E-mail address: ' . $mail);
    $assert->pageTextContains('E-mail address: ' . $new_mail);
  }

}
