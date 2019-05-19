<?php

namespace Drupal\uc_order\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
use Drupal\uc_order\OrderInterface;

/**
 * Provides 'Order is shippable' condition.
 *
 * @Condition(
 *   id = "uc_order_condition_is_shippable",
 *   label = @Translation("Check if an order can be shipped"),
 *   category = @Translation("Order"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     )
 *   }
 * )
 */
class OrderIsShippableCondition extends RulesConditionBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t("Check if an order can be shipped");
  }

  /**
   * Evaluates if the the order is shippable.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order.
   *
   * @return bool
   *   TRUE if the order is shippable.
   */
  protected function doEvaluate(OrderInterface $order) {
    return $order->isShippable();
  }

}
