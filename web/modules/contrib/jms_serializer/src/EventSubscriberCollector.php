<?php

namespace Drupal\jms_serializer;

use JMS\Serializer\EventDispatcher\EventSubscriberInterface;

/**
 * Class EventListenerCollector.
 *
 * @package Drupal\jms_serializer
 */
class EventSubscriberCollector {

  /**
   * The registered events.
   *
   * @var \JMS\Serializer\EventDispatcher\EventSubscriberInterface[]
   */
  protected $events = [];

  /**
   * Add a new subscriber.
   *
   * @param \JMS\Serializer\EventDispatcher\EventSubscriberInterface $eventSubscriber
   *   The new subscriber.
   */
  public function addSubscriber(EventSubscriberInterface $eventSubscriber) {
    $this->events[] = $eventSubscriber;
  }

  /**
   * Get the registered events.
   *
   * @return \JMS\Serializer\EventDispatcher\EventSubscriberInterface[]
   *   The events.
   */
  public function getEvents() {
    return $this->events;
  }

}
