<?php

namespace Drupal\Tests\uc_order\Traits;

use Drupal\uc_order\Entity\Order;
use Drupal\uc_order\Entity\OrderProduct;
use Drupal\Tests\uc_product\Traits\ProductTestTrait;

/**
 * Utility functions to provide orders for test purposes.
 *
 * This trait can only be used in classes which already use
 * RandomGeneratorTrait. RandomGeneratorTrait is used in all
 * the PHPUnit and Simpletest base classes.
 */
trait OrderTestTrait {
  use ProductTestTrait;

  /**
   * Creates a new order directly, without going through checkout.
   *
   * @param array $edit
   *   (optional) An associative array of order fields to change from the
   *   defaults, keys are order field names. For example, 'price' => '12.34'.
   *
   * @return \Drupal\uc_order\OrderInterface
   *   The created Order entity.
   */
  protected function createOrder(array $edit = []) {
    if (empty($edit['primary_email'])) {
      $edit['primary_email'] = $this->randomMachineName(8) . '@example.org';
    }

    $order = Order::create($edit);

    if (!isset($edit['products'])) {
      $product = $this->createProduct();
      $order->products[] = OrderProduct::create([
        'nid' => $product->nid->target_id,
        'title' => $product->title->value,
        'model' => $product->model,
        'qty' => 1,
        'cost' => $product->cost->value,
        'price' => $product->price->value,
        'weight' => $product->weight,
        'data' => [],
      ]);
    }

    $order->save();

    return Order::load($order->id());
  }

}
