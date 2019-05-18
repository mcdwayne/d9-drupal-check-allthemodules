<?php

namespace Drupal\uc_order\Plugin\Condition;

use Drupal\uc_order\OrderInterface;

/**
 * Provides 'Order subtotal amount' condition.
 *
 * @Condition(
 *   id = "uc_order_condition_subtotal",
 *   label = @Translation("Check an order's subtotal"),
 *   category = @Translation("Order"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     ),
 *     "op" = @ContextDefinition("string",
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
class OrderSubtotalCondition extends OrderConditionBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t("Check an order's subtotal");
  }

  /**
   * Compares order subtotal.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order to check.
   * @param string $op
   *   The comparison operator.
   * @param float $value
   *   The target value.
   *
   * @return bool
   *   TRUE if the order subtotal meets the specified condition.
   */
  protected function doEvaluate(OrderInterface $order, $op, $value) {
    if (is_array($order->line_items)) {
      foreach ($order->line_items as $line_item) {
        if ($line_item['type'] == 'subtotal') {
          $subtotal = $line_item['amount'];
          return $this->compareComparisonOptions($subtotal, $op, $value);
        }
      }
    }
    return FALSE;
  }

}
