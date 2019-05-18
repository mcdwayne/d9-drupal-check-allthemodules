<?php

namespace Drupal\commerce_rental_reservation\EventSubscriber;

use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RentalInstanceEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      'commerce_rental_instance.returned.post_transition' => ['setInstanceChanged'],
    ];
  }

  /**
   * Update the instances 'changed' time when it is returned so we know the last time it was on an order.
   *
   * @param \Drupal\state_machine\Event\WorkflowTransitionEvent $event
   *   The transition event.
   */
  public function setInstanceChanged(WorkflowTransitionEvent $event) {
    /** @var \Drupal\commerce_rental_reservation\Entity\RentalInstanceInterface $instance */
    $instance = $event->getEntity();
    $instance->setChangedTime(time());
  }
}