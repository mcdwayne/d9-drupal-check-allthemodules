<?php

namespace Drupal\contacts_events\EventSubscriber;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_order\Entity\OrderItemInterface;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\contacts_events\OrderStateTrait;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for booking confirmation.
 */
class BookingConfirmedSubscriber implements EventSubscriberInterface {

  use OrderStateTrait;

  /**
   * The time.
   *
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * The event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Constructs a new TimestampEventSubscriber object.
   *
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher service.
   */
  public function __construct(TimeInterface $time, EventDispatcherInterface $event_dispatcher) {
    $this->time = $time;
    $this->eventDispatcher = $event_dispatcher;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Confirming line items should be the very first step before transitioning.
    $events['commerce_order.place.pre_transition'][] = ['confirmLineItems', 999];

    // If paid in full, move directly to paid in full. Do this early so other
    // confirm subscribers are aware.
    $events['commerce_order.place.pre_transition'][] = ['checkOrderPaidInFull', 99];
    $events['contacts_events_order_items.confirm.pre_transition'][] = ['checkItemPaidInFull', 99];

    // Set the confirmed time of line items.
    $events['contacts_events_order_items.confirm.pre_transition'][] = ['setConfirmedTime', 0];

    // Make sure double transitions fire all the right events at the right time.
    $events['commerce_order.place.pre_transition'][] = ['fireOrderConfirmedPaidInFullPreTransition', -999];
    $events['commerce_order.confirmed_paid_in_full.post_transition'][] = ['fireOrderConfirmedPaidInFullPostTransition', 90];
    $events['contacts_events_order_items.confirm.pre_transition'][] = ['fireOrderConfirmedPaidInFullPreTransition', -999];
    $events['contacts_events_order_items.confirmed_paid_in_full.post_transition'][] = ['fireOrderConfirmedPaidInFullPostTransition', 90];

    return $events;
  }

  /**
   * Check whether this subscriber applies to the event.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order we are checking.
   *
   * @return bool
   *   Whether this subscriber applies to the given order.
   */
  protected function applies(OrderInterface $order) {
    return $order->bundle() == 'contacts_booking';
  }

  /**
   * Fire the additional pre transition events for paid in full and combined.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The confirm transition event.
   */
  public function fireOrderConfirmedPaidInFullPreTransition(WorkflowTransitionEvent $event) {
    $entity = $event->getEntity();
    /* @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity instanceof OrderItemInterface ? $entity->getOrder() : $entity;
    if (!$this->applies($order)) {
      return;
    }

    // We need to fire the paid in full and direct to paid in full pre
    // transition events if we have gone straight to paid in full.
    $to_state = $entity->get('state')->value;
    if ($to_state == 'paid_in_full') {
      $this->dispatchEvent('paid_in_full', 'pre_transition', $event, $entity, $to_state);
      $this->dispatchEvent('confirmed_paid_in_full', 'pre_transition', $event, $entity, $to_state);
    }
  }

  /**
   * Fire the additional post transition events for confirmed and paid in full.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The confirmed_paid_in_full transition event.
   */
  public function fireOrderConfirmedPaidInFullPostTransition(WorkflowTransitionEvent $event) {
    $entity = $event->getEntity();
    /* @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $entity instanceof OrderItemInterface ? $entity->getOrder() : $entity;
    if (!$this->applies($order)) {
      return;
    }

    // We have jumped straight to paid in full, so manually trigger the confirm
    // and paid in full transitions.
    $this->dispatchEvent($entity instanceof OrderItemInterface ? 'confirm' : 'place', 'post_transition', $event, $entity);
    $this->dispatchEvent('paid_in_full', 'post_transition', $event, $entity);
  }

  /**
   * Dispatch a workflow event.
   *
   * @param string $transition
   *   The name of the transition.
   * @param string $phase
   *   The phase, either pre_transition or post_transition.
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $original_event
   *   The original transition event.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity being transitioned.
   * @param string $to_state
   *   Optionally the state we're transitioning to. Defaults to the to state of
   *   the original event.
   */
  protected function dispatchEvent($transition, $phase, WorkflowTransitionEvent $original_event, ContentEntityInterface $entity, $to_state = NULL) {
    $workflow = $original_event->getWorkflow();
    $event_id = "{$workflow->getGroup()}.{$transition}.{$phase}";
    if (isset($to_state) && is_string($to_state)) {
      $to_state = $workflow->getState($to_state);
    }
    $event = new WorkflowTransitionEvent($original_event->getFromState(), $to_state ?? $original_event->getToState(), $workflow, $entity);
    $this->eventDispatcher->dispatch($event_id, $event);
  }

  /**
   * Check whether an order is paid in full and, if so, progress the state.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function checkOrderPaidInFull(WorkflowTransitionEvent $event) {
    /* @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    if (!$this->applies($order)) {
      return;
    }

    $balance = $order->getBalance();
    if (!$balance || $balance->isZero() || $balance->isNegative()) {
      // We only want to progress if items are also all confirmed.
      if (!$this->orderHasUnconfirmedItems($order)) {
        $state = $order->getState();
        $transition = $state->getWorkflow()->getTransition('confirmed_paid_in_full');
        $state->applyTransition($transition);
      }
    }
  }

  /**
   * Check whether an item is paid in full and, if so, progress the state.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function checkItemPaidInFull(WorkflowTransitionEvent $event) {
    /* @var \Drupal\commerce_order\Entity\OrderItemInterface $item */
    $item = $event->getEntity();
    $order = $item->getOrder();

    if (!$this->applies($order)) {
      return;
    }

    // @todo: Check item balance rather than order.
    $balance = $order->getBalance();
    if (!$balance || $balance->isZero() || $balance->isNegative()) {
      /* @var \Drupal\state_machine\Plugin\Field\FieldType\StateItem $state */
      $state = $item->get('state')->first();
      $transition = $state->getWorkflow()->getTransition('confirmed_paid_in_full');
      $state->applyTransition($transition);
    }
  }

  /**
   * Sets the order item's confirmed timestamp.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function setConfirmedTime(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_order\Entity\OrderItemInterface $item */
    $item = $event->getEntity();
    if ($this->applies($item->getOrder()) && $item->get('confirmed')->isEmpty()) {
      $item->set('confirmed', $this->time->getRequestTime());
    }
  }

  /**
   * Confirm any line items when a booking is confirmed.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function confirmLineItems(WorkflowTransitionEvent $event) {
    /* @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();

    if (!$this->applies($order)) {
      return;
    }

    foreach ($order->getItems() as $item) {
      /* @var \Drupal\state_machine\Plugin\Field\FieldType\StateItem $state */
      $state = $item->get('state')->first();
      $transitions = $state->getWorkflow()->getPossibleTransitions($state->value);
      if (isset($transitions['confirm'])) {
        $state->applyTransition($transitions['confirm']);
        $item->save();
      }
    }
  }

}
