<?php

namespace Drupal\uc_payment\Plugin\Condition;

use Drupal\uc_order\OrderInterface;
use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides 'Order payment balance' condition.
 *
 * @Condition(
 *   id = "uc_payment_condition_order_balance",
 *   label = @Translation("Check the order balance"),
 *   category = @Translation("Payment"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     ),
 *     "balance_comparison" = @ContextDefinition("string",
 *       label = @Translation("Operator"),
 *       description = @Translation("The comparison operator."),
 *       list_options_callback = "balanceOptions",
 *       assignment_restriction = "input"
 *     ),
 *     "include_authorizations" = @ContextDefinition("boolean",
 *       label = @Translation("Include authorizations?"),
 *       description = @Translation("Should 'authorization only' credit card transactions be used in calculating the order balance?"),
 *       list_options_callback = "booleanOptions",
 *       assignment_restriction = "input"
 *     )
 *   }
 * )
 */
class PaymentBalanceCondition extends RulesConditionBase {

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t("Check an order's payment balance");
  }

  /**
   * Returns balance options.
   */
  public function balanceOptions() {
    $zero = ['@zero' => uc_currency_format(0)];
    $options = [
      'less' => $this->t('Balance is less than @zero.', $zero),
      'less_equal' => $this->t('Balance is less than or equal to @zero.', $zero),
      'equal' => $this->t('Balance is equal to @zero.', $zero),
      'greater' => $this->t('Balance is greater than @zero.', $zero),
    ];

    return $options;
  }

  /**
   * Returns a TRUE/FALSE option set for boolean types.
   *
   * @return array
   *   A TRUE/FALSE options array.
   */
  public function booleanOptions() {
    return [
      0 => $this->t('No'),
      1 => $this->t('Yes'),
    ];
  }

  /**
   * Condition: Check the current order balance.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order to check.
   * @param string $balance_comparison
   *   What kind of comparison to make.
   * @param bool $include_authorizations
   *   Whether to include "authorization only" payments in the comparison.
   *
   * @return bool
   *   TRUE if the order total meets the specified condition.
   */
  protected function doEvaluate(OrderInterface $order, $balance_comparison, $include_authorizations) {
    $balance = uc_payment_balance($order);
    if ($include_authorizations) {
      foreach ((array) $order->data->cc_txns['authorizations'] as $auth_id => $data) {
        $balance -= $data['amount'];
      }
    }

    switch ($balance_comparison) {
      case 'less':
        return $balance < 0;

      case 'less_equal':
        return $balance <= 0.01;

      case 'equal':
        return $balance < 0.01 && $balance > -0.01;

      case 'greater':
        return $balance >= 0.01;
    }
  }

}
