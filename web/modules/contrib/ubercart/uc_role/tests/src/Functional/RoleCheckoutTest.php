<?php

namespace Drupal\Tests\uc_role\Functional;

use Drupal\uc_order\Entity\Order;
use Drupal\Tests\uc_store\Functional\UbercartBrowserTestBase;
use Drupal\user\Entity\User;

/**
 * Tests role assignment upon checkout.
 *
 * @group ubercart
 */
class RoleCheckoutTest extends UbercartBrowserTestBase {

  public static $modules = ['uc_payment', 'uc_payment_pack', 'uc_role'];

  /**
   * Authenticated but unprivileged user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $customer;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create a simple customer user account.
    $this->customer = $this->drupalCreateUser();

    // Ensure test mails are logged.
    \Drupal::configFactory()->getEditable('system.mail')
      ->set('interface.uc_order', 'test_mail_collector')
      ->save();
  }

  /**
   * Tests that roles are properly assigned after checkout.
   */
  public function testCheckoutRoleAssignment() {
    $this->drupalLogin($this->adminUser);
    $method = $this->createPaymentMethod('other');

    // Add role assignment to the test product.
    $rid = $this->drupalCreateRole(['access content']);
    $this->drupalPostForm('node/' . $this->product->id() . '/edit/features', ['feature' => 'role'], 'Add');
    $this->drupalPostForm(NULL, ['role' => $rid], 'Save feature');

    // Process an anonymous, shippable order.
    $order = $this->createOrder([
      'uid' => 0,
      'payment_method' => $method['id'],
    ]);
    $order->products[1]->data->shippable = 1;
    $order->save();
    uc_payment_enter($order->id(), 'other', $order->getTotal());

    // Find the order uid.
    $uid = db_query('SELECT uid FROM {uc_orders} ORDER BY order_id DESC')->fetchField();
    $account = User::load($uid);
    // @todo Re-enable when Rules is available.
    // $this->assertTrue($account->hasRole($rid), 'New user was granted role.');
    $order = Order::load($order->id());
    $this->assertEquals($order->getStatusId(), 'payment_received', 'Shippable order was set to payment received.');

    // 4 e-mails: new account, customer invoice, admin invoice, role assignment.
    $this->assertMailString('subject', 'Account details', 4, 'New account email was sent');
    $this->assertMailString('subject', 'Your Order at Ubercart', 4, 'Customer invoice was sent');
    $this->assertMailString('subject', 'New Order at Ubercart', 4, 'Admin notification was sent');
    // @todo Re-enable when Rules is available.
    // $this->assertMailString('subject', 'role granted', 4, 'Role assignment notification was sent');

    \Drupal::state()->set('system.test_mail_collector', []);

    // Test again with an existing authenticated user and a non-shippable order.
    $order = $this->createOrder([
      'uid' => 0,
      'primary_email' => $this->customer->getEmail(),
      'payment_method' => $method['id'],
    ]);
    $order->products[2]->data->shippable = 0;
    $order->save();
    uc_payment_enter($order->id(), 'other', $order->getTotal());
    $account = User::load($this->customer->id());
    // @todo Re-enable when Rules is available.
    //$this->assertTrue($account->hasRole($rid), 'Existing user was granted role.');
    $order = Order::load($order->id());
    $this->assertEquals($order->getStatusId(), 'completed', 'Non-shippable order was set to completed.');

    // 3 e-mails: customer invoice, admin invoice, role assignment.
    $this->assertNoMailString('subject', 'Account details', 4, 'New account email was sent');
    $this->assertMailString('subject', 'Your Order at Ubercart', 4, 'Customer invoice was sent');
    $this->assertMailString('subject', 'New Order at Ubercart', 4, 'Admin notification was sent');
    // @todo Re-enable when Rules is available.
    // $this->assertMailString('subject', 'role granted', 4, 'Role assignment notification was sent');
  }

}
