<?php

namespace Drupal\Tests\uc_order\Functional;

use Drupal\uc_order\Entity\Order;
use Drupal\uc_store\Address;
use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;

/**
 * Tests for Ubercart orders.
 *
 * @group ubercart
 */
class OrderTest extends UbercartBrowserTestBase {

  /**
   * Authenticated but unprivileged user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $customer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Need page_title_block because we test page titles.
    $this->drupalPlaceBlock('page_title_block');

    // Create a simple customer user account.
    $this->customer = $this->drupalCreateUser(['view own orders']);
  }

  /**
   * Tests order entity API functions.
   */
  public function testOrderApi() {
    // Test defaults.
    $order = Order::create();
    $order->save();
    $this->assertEquals($order->getOwnerId(), 0, 'New order is anonymous.');
    $this->assertEquals($order->getStatusId(), 'in_checkout', 'New order is in checkout.');

    $order = Order::create([
      'uid' => $this->customer->id(),
      'order_status' => uc_order_state_default('completed'),
    ]);
    $order->save();
    $this->assertEquals($order->getOwnerId(), $this->customer->id(), 'New order has correct uid.');
    $this->assertEquals($order->getStatusId(), 'completed', 'New order is marked completed.');

    // Test deletion.
    $order->delete();
    $deleted_order = Order::load($order->id());
    $this->assertFalse($deleted_order, 'Order was successfully deleted');
  }

  /**
   * Tests order CRUD operations.
   */
  public function testOrderEntity() {
    $order = Order::create();
    $this->assertEquals($order->getOwnerId(), 0, 'New order is anonymous.');
    $this->assertEquals($order->getStatusId(), 'in_checkout', 'New order is in checkout.');

    $name = $this->randomMachineName();
    $order = Order::create([
      'uid' => $this->customer->id(),
      'order_status' => 'completed',
      'billing_first_name' => $name,
      'billing_last_name' => $name,
    ]);
    $this->assertEquals($order->getOwnerId(), $this->customer->id(), 'New order has correct uid.');
    $this->assertEquals($order->getStatusId(), 'completed', 'New order is marked completed.');
    $this->assertEquals($order->getAddress('billing')->getFirstName(), $name, 'New order has correct name.');
    $this->assertEquals($order->getAddress('billing')->getLastName(), $name, 'New order has correct name.');

    // Test deletion.
    $order->save();
    $storage = \Drupal::entityTypeManager()->getStorage('uc_order');
    $entities = $storage->loadMultiple([$order->id()]);
    $storage->delete($entities);

    $storage->resetCache([$order->id()]);
    $deleted_order = Order::load($order->id());
    $this->assertFalse($deleted_order, 'Order was successfully deleted');
  }

  /**
   * Tests order entity CRUD hooks.
   */
  public function testEntityHooks() {
    \Drupal::service('module_installer')->install(['entity_crud_hook_test']);

    $GLOBALS['entity_crud_hook_test'] = [];
    $order = Order::create();
    $order->save();

    $this->assertHookMessage('entity_crud_hook_test_entity_presave called for type uc_order');
    $this->assertHookMessage('entity_crud_hook_test_entity_insert called for type uc_order');

    $GLOBALS['entity_crud_hook_test'] = [];
    $order = Order::load($order->id());

    $this->assertHookMessage('entity_crud_hook_test_entity_load called for type uc_order');

    $GLOBALS['entity_crud_hook_test'] = [];
    $order->save();

    $this->assertHookMessage('entity_crud_hook_test_entity_presave called for type uc_order');
    $this->assertHookMessage('entity_crud_hook_test_entity_update called for type uc_order');

    $GLOBALS['entity_crud_hook_test'] = [];
    $order->delete();

    $this->assertHookMessage('entity_crud_hook_test_entity_delete called for type uc_order');
  }

  /**
   * Tests admin order creation.
   */
  public function testOrderCreation() {
    $this->drupalLogin($this->adminUser);
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $edit = [
      'customer_type' => 'search',
      'customer[email]' => $this->customer->mail->value,
    ];
    $this->drupalPostForm('admin/store/orders/create', $edit, 'Search');

    $edit['customer[uid]'] = $this->customer->id();
    $this->drupalPostForm(NULL, $edit, 'Create order');
    $assert->pageTextContains('Order created by the administration.', 'Order created by the administration.');
    $this->assertFieldByName('uid_text', $this->customer->id(), 'The customer UID appears on the page.');

    $order_ids = \Drupal::entityQuery('uc_order')
      ->condition('uid', $this->customer->id())
      ->execute();
    $order_id = reset($order_ids);
    $this->assertTrue($order_id, format_string('Found order ID @order_id', ['@order_id' => $order_id]));

    $this->drupalGet('admin/store/orders/view');
    $assert->linkByHrefExists('admin/store/orders/' . $order_id, 0, 'View link appears on order list.');
    $assert->pageTextContains('Pending', 'New order is "Pending".');

    $this->drupalGet('admin/store/customers/orders/' . $this->customer->id());
    $assert->linkByHrefExists('admin/store/orders/' . $order_id, 0, 'View link appears on customer order list.');

    $this->clickLink('Create order for this customer');
    $assert->pageTextContains('Order created by the administration.');
    $this->assertFieldByName('uid_text', $this->customer->id(), 'The customer UID appears on the page.');
  }

  /**
   * Tests order admin View.
   */
  public function testOrderView() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $order = $this->ucCreateOrder($this->customer);

    $this->drupalLogin($this->adminUser);
    $this->drupalGet('admin/store/orders/' . $order->id());

    $billing_address = $order->getAddress('billing');
    // Check for billing first name, billing last name, billing street1
    // and billing street2.
    $assert->pageTextContains(mb_strtoupper($billing_address->getFirstName()));
    $assert->pageTextContains(mb_strtoupper($billing_address->getLastName()));
    $assert->pageTextContains(mb_strtoupper($billing_address->getStreet1()));
    $assert->pageTextContains(mb_strtoupper($billing_address->getStreet2()));
    // Some country formats don't use City in addresses.
    $country = \Drupal::service('country_manager')->getCountry($billing_address->getCountry());
    if (strpos(implode('', $country->getAddressFormat()), 'city') === FALSE) {
      // Check for billing city.
      $assert->pageTextContains(mb_strtoupper($billing_address->getCity()));
    }

    $delivery_address = $order->getAddress('delivery');
    // Check for delivery first name, delivery last name, delivery street1
    // and delivery street2.
    $assert->pageTextContains(mb_strtoupper($delivery_address->getFirstName()));
    $assert->pageTextContains(mb_strtoupper($delivery_address->getLastName()));
    $assert->pageTextContains(mb_strtoupper($delivery_address->getStreet1()));
    $assert->pageTextContains(mb_strtoupper($delivery_address->getStreet2()));
    $country = \Drupal::service('country_manager')->getCountry($delivery_address->getCountry());
    if (strpos(implode('', $country->getAddressFormat()), 'city') === FALSE) {
      // Check for delivery city.
      $assert->pageTextContains(mb_strtoupper($delivery_address->getCity()));
    }

    $assert->linkExists($order->getOwnerId(), 0, 'Link to customer account page found.');
    $assert->linkExists($order->getEmail(), 0, 'Link to customer email address found.');
  }

  /**
   * Tests the customer View of the completed order.
   */
  public function testOrderCustomerView() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $order = $this->ucCreateOrder($this->customer);

    // Update the status to pending, so the user can see the order on the
    // "My order history" page.
    $order->setStatusId('pending');
    $order->save();

    $this->drupalLogin($this->customer);
    $this->drupalGet('user/' . $this->customer->id() . '/orders');
    $assert->pageTextContains('My order history');
    $assert->pageTextContains('Pending', 'Order status is visible to the customer.');

    $this->drupalGet('user/' . $this->customer->id() . '/orders/' . $order->id());
    $assert->statusCodeEquals(200, 'Customer can view their own order.');
    $address = $order->getAddress('billing');
    // Check for customer first and last name.
    $assert->pageTextContains(mb_strtoupper($address->getFirstName()));
    $assert->pageTextContains(mb_strtoupper($address->getLastName()));

    $this->drupalGet('admin/store/orders/' . $order->id());
    $assert->statusCodeEquals(403, 'Customer may not see the admin view of their order.');

    $this->drupalGet('admin/store/orders/' . $order->id() . '/edit');
    $assert->statusCodeEquals(403, 'Customer may not edit orders.');
  }

  /**
   * Tests admin editing of orders.
   */
  public function testOrderEditing() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $order = $this->ucCreateOrder($this->customer);

    $this->drupalLogin($this->adminUser);
    $edit = [
      'billing[first_name]' => $this->randomMachineName(8),
      'billing[last_name]' => $this->randomMachineName(15),
    ];
    $this->drupalPostForm('admin/store/orders/' . $order->id() . '/edit', $edit, 'Save changes');
    $assert->pageTextContains('Order changes saved.');
    $this->assertFieldByName('billing[first_name]', $edit['billing[first_name]'], 'Billing first name changed.');
    $this->assertFieldByName('billing[last_name]', $edit['billing[last_name]'], 'Billing last name changed.');
  }

  /**
   * Tests admin and automatic changing of order state and status.
   */
  public function testOrderState() {
    $this->drupalLogin($this->adminUser);

    // Check that the default order state and status is correct.
    $this->drupalGet('admin/store/config/orders');
    $this->assertFieldByName('order_states[in_checkout][default]', 'in_checkout', 'State defaults to correct default status.');
    $this->assertEquals(uc_order_state_default('in_checkout'), 'in_checkout', 'uc_order_state_default() returns correct default status.');
    $order = $this->ucCreateOrder($this->customer);
    $this->assertEquals($order->getStateId(), 'in_checkout', 'Order has correct default state.');
    $this->assertEquals($order->getStatusId(), 'in_checkout', 'Order has correct default status.');

    // Create a custom "in checkout" order status with a lower weight.
    $this->drupalGet('admin/store/config/orders');
    $this->clickLink('Create custom order status');
    $edit = [
      'id' => strtolower($this->randomMachineName()),
      'name' => $this->randomMachineName(),
      'state' => 'in_checkout',
      'weight' => -15,
    ];
    $this->drupalPostForm(NULL, $edit, 'Create');
    $this->assertEquals(uc_order_state_default('in_checkout'), $edit['id'], 'uc_order_state_default() returns lowest weight status.');

    // Set "in checkout" state to default to the new status.
    $this->drupalPostForm(NULL, ['order_states[in_checkout][default]' => $edit['id']], 'Save configuration');
    $this->assertFieldByName('order_states[in_checkout][default]', $edit['id'], 'State defaults to custom status.');
    $order = $this->ucCreateOrder($this->customer);
    $this->assertEquals($order->getStatusId(), $edit['id'], 'Order has correct custom status.');
  }

  /**
   * Tests using custom order statuses.
   */
  public function testCustomOrderStatus() {
    /** @var \Drupal\Tests\WebAssert $assert */
    $assert = $this->assertSession();

    $order = $this->ucCreateOrder($this->customer);

    $this->drupalLogin($this->adminUser);

    // Update an order status label.
    $this->drupalGet('admin/store/config/orders');
    $title = $this->randomMachineName();
    $edit = [
      'order_statuses[in_checkout][name]' => $title,
    ];
    $this->drupalPostForm(NULL, $edit, 'Save configuration');
    $this->assertFieldByName('order_statuses[in_checkout][name]', $title, 'Updated status title found.');

    // Confirm the updated label is displayed.
    $this->drupalGet('admin/store/orders/view');
    // Check that order displays updated status title.
    $assert->pageTextContains($title);

    // Create a custom order status.
    $this->drupalGet('admin/store/config/orders');
    $this->clickLink('Create custom order status');
    $edit = [
      'id' => strtolower($this->randomMachineName()),
      'name' => $this->randomMachineName(),
      'state' => array_rand(uc_order_state_options_list()),
      'weight' => mt_rand(-10, 10),
    ];
    $this->drupalPostForm(NULL, $edit, 'Create');
    $assert->pageTextContains($edit['id'], 'Custom status ID found.');
    $this->assertFieldByName('order_statuses[' . $edit['id'] . '][name]', $edit['name'], 'Custom status title found.');
    $this->assertFieldByName('order_statuses[' . $edit['id'] . '][weight]', $edit['weight'], 'Custom status weight found.');

    // Set an order to the custom status.
    $this->drupalPostForm('admin/store/orders/' . $order->id(), ['status' => $edit['id']], 'Update');
    $this->drupalGet('admin/store/orders/view');
    $assert->pageTextContains($edit['name'], 'Order displays custom status title.');

    // Delete the custom order status.
    $this->drupalPostForm('admin/store/config/orders', ['order_statuses[' . $edit['id'] . '][remove]' => 1], 'Save configuration');
    $assert->pageTextNotContains($edit['id'], 'Deleted status ID not found.');
  }

  /**
   * Helper function for creating an order programmatically.
   */
  protected function ucCreateOrder($customer) {
    $order = Order::create([
      'uid' => $customer->id(),
    ]);
    $order->save();
    uc_order_comment_save($order->id(), 0, 'Order created programmatically.', 'admin');

    $order_ids = \Drupal::entityQuery('uc_order')
      ->condition('order_id', $order->id())
      ->execute();
    $this->assertTrue(in_array($order->id(), $order_ids), format_string('Found order ID @order_id', ['@order_id' => $order->id()]));

    $country_manager = \Drupal::service('country_manager');
    $country = array_rand($country_manager->getEnabledList());
    $zones = $country_manager->getZoneList($country);

    $delivery_address = Address::create();
    $delivery_address
      ->setFirstName($this->randomMachineName(12))
      ->setLastName($this->randomMachineName(12))
      ->setStreet1($this->randomMachineName(12))
      ->setStreet2($this->randomMachineName(12))
      ->setCity($this->randomMachineName(12))
      ->setPostalCode(mt_rand(10000, 99999))
      ->setCountry($country);
    if (!empty($zones)) {
      $delivery_address->setZone(array_rand($zones));
    }

    $billing_address = Address::create();
    $billing_address
      ->setFirstName($this->randomMachineName(12))
      ->setLastName($this->randomMachineName(12))
      ->setStreet1($this->randomMachineName(12))
      ->setStreet2($this->randomMachineName(12))
      ->setCity($this->randomMachineName(12))
      ->setPostalCode(mt_rand(10000, 99999))
      ->setCountry($country);
    if (!empty($zones)) {
      $billing_address->setZone(array_rand($zones));
    }

    $order->setAddress('delivery', $delivery_address)
      ->setAddress('billing', $billing_address)
      ->save();

    // Force the order to load from the DB instead of the entity cache.
    $db_order = \Drupal::entityTypeManager()->getStorage('uc_order')->loadUnchanged($order->id());
    // Compare delivery and billing addresses to those loaded from the database.
    $db_delivery_address = $db_order->getAddress('delivery');
    $db_billing_address = $db_order->getAddress('billing');
    $this->assertEquals($delivery_address, $db_delivery_address, 'Delivery address is equal to delivery address in database.');
    $this->assertEquals($billing_address, $db_billing_address, 'Billing address is equal to billing address in database.');

    return $order;
  }

  /**
   * Helper function for testing order entity CRUD hooks.
   */
  protected function assertHookMessage($text, $message = NULL, $group = 'Other') {
    if (!isset($message)) {
      $message = $text;
    }
    return $this->assertTrue(array_search($text, $GLOBALS['entity_crud_hook_test']) !== FALSE, $message, $group);
  }

}
