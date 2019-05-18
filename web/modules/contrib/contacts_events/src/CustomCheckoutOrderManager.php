<?php

namespace Drupal\contacts_events;

use Drupal\commerce_checkout\CheckoutOrderManager;
use Drupal\commerce_order\Entity\OrderInterface;

/**
 * Custom CheckoutOrderManager implementation.
 *
 * Supports modifying an order even once it's been completed.
 */
class CustomCheckoutOrderManager extends CheckoutOrderManager {

  /**
   * {@inheritdoc}
   */
  public function getCheckoutStepId(OrderInterface $order, $requested_step_id = NULL) {
    // Get the available steps.
    $checkout_flow = $this->getCheckoutFlow($order);
    $available_step_ids = array_keys($checkout_flow->getPlugin()->getVisibleSteps());

    // Get the current step based on the order status.
    $order_progress_step = $order->get('checkout_step')->value ?: reset($available_step_ids);

    // Default the requested step to the first step, unless the current step is
    // payment, as we need to let that process run.
    if (!$requested_step_id && $order_progress_step != 'payment') {
      $requested_step_id = reset($available_step_ids);
    }

    // If there was no specific request or the request is the current step,
    // we can simply return the order step.
    if (empty($requested_step_id) || $requested_step_id == $order_progress_step) {
      return $order_progress_step;
    }

    // Otherwise, we need to check that the requested step is before the current
    // order step to prevent jumping forward.
    if (in_array($requested_step_id, $available_step_ids)) {
      // Always allow tickets step to be accessed. This is partly to avoid the
      // situation where orders created via other means cannot be managed
      // through the custom checkout because they do not have checkout_flow or
      // checkout_step set.
      if ($requested_step_id == 'tickets') {
        return $requested_step_id;
      }

      $requested_step_index = array_search($requested_step_id, $available_step_ids);
      $selected_step_index = array_search($order_progress_step, $available_step_ids);

      // If the step is before the current, we can allow it.
      if ($requested_step_index <= $selected_step_index) {
        return $requested_step_id;
      }
    }

    return $order_progress_step;
  }

}
