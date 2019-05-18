<?php

namespace Drupal\contacts_events\Entity;

use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\contacts_events\OrderStateTrait;

/**
 * Implementations for Payment hooks.
 */
class PaymentHooks {

  use OrderStateTrait;

  /**
   * After saving, handle transitioning orders and items to/from paid in full.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentInterface $payment
   *   The payment entity.
   * @param \Drupal\commerce_payment\Entity\PaymentInterface|null $original
   *   The original payment entity, if any.
   */
  public function postSave(PaymentInterface $payment, PaymentInterface $original = NULL) {
    // Only act if the payment has transitioned to or from completed.
    $current_completed = $payment->getState()->value == 'completed';
    $original_completed = $original ? $original->getState()->value == 'completed' : FALSE;
    if (!($current_completed xor $original_completed)) {
      return;
    }

    $order = $payment->getOrder();

    // Only operate on bookings.
    if ($order->bundle() != 'contacts_booking') {
      return;
    }

    // Get the applicable transition.
    $transition_id = $order->getBalance()->isPositive() ? 'payment_undone' : 'paid_in_full';

    // First transition the line items.
    foreach ($order->getItems() as $item) {
      /** @var \Drupal\state_machine\Plugin\Field\FieldType\StateItem $state */
      $state = $item->get('state')->first();
      $transitions = $state->getWorkflow()->getPossibleTransitions($state->value);
      if (isset($transitions[$transition_id])) {
        $state->applyTransition($transitions[$transition_id]);
        $item->save();
      }
    }

    // Then transition the order.
    $state = $order->getState();
    $transitions = $state->getWorkflow()->getPossibleTransitions($state->value);
    if (isset($transitions[$transition_id])) {
      $state->applyTransition($transitions[$transition_id]);
      $order->save();
    }
  }

}
