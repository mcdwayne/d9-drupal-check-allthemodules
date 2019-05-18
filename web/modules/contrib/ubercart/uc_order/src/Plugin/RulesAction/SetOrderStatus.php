<?php

namespace Drupal\uc_order\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\uc_order\OrderInterface;
use Drupal\uc_order\Entity\OrderStatus;

/**
 * Provides a 'Set order status' action.
 *
 * @RulesAction(
 *   id = "uc_order_update_status",
 *   label = @Translation("Update the order status"),
 *   category = @Translation("Order"),
 *   context = {
 *     "order" = @ContextDefinition("entity:uc_order",
 *       label = @Translation("Order")
 *     ),
 *     "order_status" = @ContextDefinition("string",
 *       label = @Translation("Status"),
 *       list_options_callback = "getOptionsList"
 *     )
 *   }
 * )
 */
class SetOrderStatus extends RulesActionBase {

  /**
   * Order status options callback.
   */
  public function getOptionsList() {
    return OrderStatus::getOptionsList();
  }

  /**
   * Updates an order's status.
   *
   * @param \Drupal\uc_order\OrderInterface $order
   *   The order entity.
   * @param string $status
   *   The order status.
   */
  protected function doExecute(OrderInterface $order, $status) {
    $order->setStatusId($status)->save();
  }

}
