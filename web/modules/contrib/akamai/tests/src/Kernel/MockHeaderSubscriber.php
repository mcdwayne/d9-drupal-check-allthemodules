<?php

namespace Drupal\Tests\akamai\Kernel;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\akamai\Event\AkamaiHeaderEvents;

/**
 * Mock Event Subscriber for testing.
 */
class MockHeaderSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AkamaiHeaderEvents::HEADER_CREATION][] = ['onHeaderCreation'];
    return $events;
  }

  /**
   * Add cache tags header on cacheable responses.
   *
   * @param \Drupal\akamai\Event\AkamaiHeaderEvents $event
   *   The event to process.
   */
  public function onHeaderCreation(AkamaiHeaderEvents $event) {
    $event->data[] = 'helloworld';
  }

}
