<?php

namespace Drupal\erf_commerce\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\state_machine\Event\WorkflowTransitionEvent;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Class OrderCompleteSubscriber.
 */
class OrderCompleteSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Entity\EntityTypeManager definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * Constructs a new OrderCompleteSubscriber object.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['commerce_order.place.post_transition'] = ['orderCompleteHandler'];
    return $events;
  }

  /**
   * This method is called whenever the commerce_order.place.post_transition event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function orderCompleteHandler(WorkflowTransitionEvent $event) {
    if ($event->getToState()->getId() !== 'completed') {
      return;
    }

    /** @var \Drupal\commerce_order\Entity\OrderInterface $order */
    $order = $event->getEntity();
    $order_items = $order->getItems();

    // Lock any registrations linked to items in this cart.
    foreach ($order_items as $order_item) {
      // See if this order item is attached to a registration.
      $registration = $this->entityTypeManager->getStorage('registration')->loadByProperties([
        'commerce_order_item_id' => $order_item->id(),
      ]);

      // If there's a registration for this order item, lock the registration.
      if ($registration) {
        // There should only be one registration with a given commerce order
        // item id.
        $registration = reset($registration);
        $registration->lock()->save();
      }
    }
  }

}
