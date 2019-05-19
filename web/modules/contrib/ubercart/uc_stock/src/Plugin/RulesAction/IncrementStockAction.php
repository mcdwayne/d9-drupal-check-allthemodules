<?php

namespace Drupal\uc_stock\Plugin\RulesAction;

use Drupal\uc_order\OrderInterface;
use Drupal\uc_order\OrderProductInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Increment stock' action.
 *
 * @RulesAction(
 *   id = "uc_stock_action_increment_stock",
 *   label = @Translation("Increment stock of products on the order with tracking activated"),
 *   category = @Translation("Stock"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     )
 *   }
 * )
 */
class IncrementStockAction extends RulesActionBase {

  /**
   * Increases the stock of ordered products.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order entity.
   */
  protected function doExecute(OrderInterface $order) {
    if (is_array($order->products)) {
      array_walk($order->products, [$this, 'incrementCallback'], $order);
    }
  }

  /**
   * Increment a product's stock.
   *
   * @param \Drupal\uc_order\OrderProductInterface $product
   *   The product whose stock is being adjusted.
   * @param mixed $key
   *   The array key currently being modified, needed so this function can be
   *   used as a callback of array_walk().
   * @param \Drupal\uc_order\OrderInterface $order
   *   Order object.
   */
  protected function incrementCallback(OrderProductInterface $product, $key, OrderInterface $order) {
    $product->qty->value = -$product->qty->value;
    return uc_stock_adjust_product_stock($product, $key, $order);
  }

}
