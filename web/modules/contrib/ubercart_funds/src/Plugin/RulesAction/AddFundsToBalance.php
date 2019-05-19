<?php

namespace Drupal\ubercart_funds\Plugin\RulesAction;

use Drupal\uc_order\OrderInterface;
use Drupal\rules\Core\RulesActionBase;

/**
 * Add funds to balance rules action.
 *
 * @RulesAction(
 *   id = "uc_funds_add_funds_to_balance",
 *   label = @Translation("Add funds to balance"),
 *   category = @Translation("Ubercart funds"),
 *   context = {
 *     "entity" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order"),
 *       description = @Translation("The deposit order which is updated")
 *     )
 *   }
 * )
 */
class AddFundsToBalance extends RulesActionBase {

  /**
   * Update account balance.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order update triggered by rules.
   */
  protected function doExecute(OrderInterface $order) {
    if ($order->getStatusId() == 'pending') {
      foreach ($order->products as $product) {
        // Check if it's a deposit and if the order is paid.
        $is_deposit = preg_match('/^deposit_[0-9]*/', $product->get('model')->getValue()[0]['value']);
        if ($is_deposit) {
          \Drupal::service('ubercart_funds.transaction_manager')->addDepositToBalance($order, $product);
        }
      }
    }
  }

}
