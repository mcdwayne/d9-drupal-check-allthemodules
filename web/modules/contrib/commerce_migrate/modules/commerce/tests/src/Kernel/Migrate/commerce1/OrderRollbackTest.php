<?php

namespace Drupal\Tests\commerce_migrate_commerce\Kernel\Migrate\commerce1;

use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_order\Entity\Order;

/**
 * Tests rollback of order migration.
 *
 * @group commerce_migrate
 * @group commerce_migrate_commerce1
 */
class OrderRollbackTest extends OrderTest {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'commerce_order',
    'commerce_price',
    'commerce_store',
  ];

  /**
   * Test order migration from Drupal 7 to 8.
   */
  public function testOrder() {
    parent::testOrder();

    // Rollback orders.
    $this->executeRollback('commerce1_order');

    // Test that the orders no longer exist.
    $order_ids = [1, 2, 3];
    foreach ($order_ids as $order_id) {
      $order = Order::load($order_id);
      $this->assertFalse($order, "Order $order_id exists.");
    }
    // Test that the order items still exist.
    $order_item_ids = [1, 2, 3, 4];
    foreach ($order_item_ids as $order_item_id) {
      $order_item = OrderItem::load($order_item_id);
      $this->assertInstanceOf(OrderItem::class, $order_item, "Order item $order_item_id does not exist.");
    }
  }

}
