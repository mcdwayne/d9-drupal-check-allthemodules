<?php

namespace Drupal\uc_order\Plugin\Condition;

use Drupal\uc_order\OrderInterface;

/**
 * Provides 'Order total value' condition.
 *
 * @Condition(
 *   id = "uc_order_condition_total",
 *   label = @Translation("Check an order's total"),
 *   category = @Translation("Order"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     ),
 *     "operator" = @ContextDefinition("string",
 *       label = @Translation("Operator"),
 *       description = @Translation("The comparison operator."),
 *       default_value = "==",
 *       list_options_callback = "comparisonOptions",
 *       assignment_restriction = "input"
 *     ),
 *     "value" = @ContextDefinition("float",
 *       label = @Translation("Data value"),
 *       description = @Translation("The value to compare the data with.")
 *     )
 *   }
 * )
 */
class OrderTotalCondition extends OrderConditionBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t("Check an order's total");
  }

  /**
   * Compares order total.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order to check.
   * @param string $operator
   *   The comparison operator.
   * @param float $value
   *   The target value.
   *
   * @return bool
   *   TRUE if the order total meets the specified condition.
   */
  protected function doEvaluate(OrderInterface $order, $operator, $value) {
    return $this->compareComparisonOptions($order->getTotal(), $operator, $value);
  }

}
