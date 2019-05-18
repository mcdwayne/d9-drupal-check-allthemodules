<?php

namespace Drupal\commerce_cib\EventSubscriber;

use Drupal\commerce_cib\Event\CibEvents;
use Drupal\commerce_cib\Event\NoCommunication;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class NoCommunicationEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      CibEvents::NO_COMMUNICATION => 'displayMessage',
    ];
    return $events;
  }

  /**
   * Just throw an exception to display the standard error message.
   *
   * @param \Drupal\commerce_cib\Event\NoCommunication $event
   *   The event.
   */
  public function displayMessage(NoCommunication $event) {
    throw new PaymentGatewayException('CIB payment has failed.');
  }

}
