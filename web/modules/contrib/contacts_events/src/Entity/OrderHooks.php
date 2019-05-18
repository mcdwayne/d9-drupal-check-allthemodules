<?php

namespace Drupal\contacts_events\Entity;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\contacts_events\OrderStateTrait;

/**
 * Implementations for Order hooks.
 */
class OrderHooks {

  use OrderStateTrait;

  /**
   * Prior to saving, update the state if have made modifications.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order item.
   */
  public function presave(OrderInterface $order) {
    // Only operate on bookings.
    if ($order->bundle() != 'contacts_booking') {
      return;
    }

    // Check if we are in a state that can be modified.
    $state = $order->getState();
    $transitions = $state->getWorkflow()->getTransitions();
    if (!isset($transitions['modified'])) {
      return;
    }

    // If there are any unconfirmed line items, we should mark as modified.
    if ($this->orderHasUnconfirmedItems($order)) {
      $state->applyTransition($transitions['modified']);
    }
  }

}
