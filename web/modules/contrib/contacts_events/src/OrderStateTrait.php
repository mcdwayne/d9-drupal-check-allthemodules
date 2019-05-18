<?php

namespace Drupal\contacts_events;

use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Trait for common order state checks and processes.
 */
trait OrderStateTrait {

  /**
   * Check if an order has unconfirmed items.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order to check.
   *
   * @return bool
   *   Whether there are unconfirmed items.
   */
  protected function orderHasUnconfirmedItems(OrderInterface $order) {
    foreach ($order->getItems() as $item) {
      /* @var \Drupal\state_machine\Plugin\Field\FieldType\StateItem $state */
      $item_state = $item->get('state')->first();
      $item_transitions = $item_state->getWorkflow()->getPossibleTransitions($item_state->value);
      if (isset($item_transitions['confirm'])) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
