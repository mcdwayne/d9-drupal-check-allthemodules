<?php

namespace Drupal\uc_stock\Plugin\RulesAction;

use Drupal\uc_order\OrderInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Provides a 'Decrement stock' action.
 *
 * @RulesAction(
 *   id = "uc_stock_action_decrement_stock",
 *   label = @Translation("Decrement stock of products on the order with tracking activated"),
 *   category = @Translation("Stock"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     )
 *   }
 * )
 */
class DecrementStockAction extends RulesActionBase {

  /**
   * Decreases the stock of ordered products.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order entity.
   */
  protected function doExecute(OrderInterface $order) {
    if (is_array($order->products)) {
      array_walk($order->products, 'uc_stock_adjust_product_stock', $order);
    }
  }

}
