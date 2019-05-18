<?php

namespace Drupal\commerce_cib\EventSubscriber;

use Drupal\commerce_cib\Event\CibEvents;
use Drupal\commerce_cib\Event\FailedInitialization;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FailedInitializationEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      CibEvents::FAILED_INITIALIZATION => 'displayMessage',
    ];
    return $events;
  }

  /**
   * Just throw an exception to display the standard error message.
   *
   * @param \Drupal\commerce_cib\Event\FailedInitialization $event
   *   The event.
   */
  public function displayMessage(FailedInitialization $event) {
    throw new PaymentGatewayException('CIB payment has failed.');
  }

}
