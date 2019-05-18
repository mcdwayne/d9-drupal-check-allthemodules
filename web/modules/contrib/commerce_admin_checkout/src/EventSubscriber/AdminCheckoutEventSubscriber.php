<?php

namespace Drupal\commerce_admin_checkout\EventSubscriber;

use Drupal\commerce_admin_checkout\Event\AdminCheckoutEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminCheckoutEventSubscriber implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    $events = [
      AdminCheckoutEvent::CHECKOUT_ASSIGN => ['onAdminCheckoutOrderAssign', 100]
    ];
    return $events;
  }

  /**
   * @param \Drupal\commerce_admin_checkout\Event\AdminCheckoutEvent $event
   */
  public function onAdminCheckoutOrderAssign(AdminCheckoutEvent $event) {
    // Actually do the assignment.
    $event->getOrder()->setCustomer($event->getAccount());
    $event->getOrder()->setEmail($event->getAccount()->getEmail());
    $event->getOrder()->save();
  }


}
