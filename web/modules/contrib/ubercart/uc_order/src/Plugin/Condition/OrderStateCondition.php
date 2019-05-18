<?php

namespace Drupal\uc_order\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_order\OrderStatusInterface;

/**
 * Provides 'Check order's workflow state' condition.
 *
 * @Condition(
 *   id = "uc_order_condition_order_state",
 *   label = @Translation("Check an order's state"),
 *   category = @Translation("Order"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     ),
 *     "order_state" = @ContextDefinition("entity:uc_order_status",
 *       label = @Translation("Order state"),
 *       list_options_callback = "orderStateOptions",
 *       assignment_restriction = "input"
 *     )
 *   }
 * )
 */
class OrderStateCondition extends RulesConditionBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t("Check an order's state");
  }

  /**
   * Options callback.
   *
   * @return array
   *   An array of order states.
   */
  public function orderStateOptions() {
    return uc_order_state_options_list();
  }

  /**
   * Checks the current order state.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order.
   * @param \Drupal\uc_order\OrderStatusInterface $order_state
   *   The order state.
   *
   * @return bool
   *   TRUE if the order has one of the given order states.
   */
  protected function doEvaluate(OrderInterface $order, OrderStatusInterface $order_state) {
    return ($order->getStateId() == $order_state);
  }

}
