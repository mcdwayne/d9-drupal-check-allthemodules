<?php

namespace Drupal\Tests\akamai\Kernel\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\akamai\Event\AkamaiHeaderEvents;
use Drupal\akamai\Event\AkamaiPurgeEvents;

/**
 * Mock Event Subscriber for testing.
 */
class MockSubscriber implements EventSubscriberInterface {

  /**
   * Storage of the last event.
   *
   * @var Symfony\Component\EventDispatcher\Event
   */
  public $event;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AkamaiHeaderEvents::HEADER_CREATION][] = ['onHeaderCreation'];
    $events[AkamaiPurgeEvents::PURGE_CREATION][] = ['onPurgeCreation'];
    return $events;
  }

  /**
   * Add cache tags header on cacheable responses.
   *
   * @param \Drupal\akamai\Event\AkamaiHeaderEvents $event
   *   The event to process.
   */
  public function onHeaderCreation(AkamaiHeaderEvents $event) {
    $this->event = $event;
    $event->data[] = 'on_header_creation';
  }

  /**
   * Process purge creation event.
   *
   * @param \Drupal\akamai\Event\AkamaiPurgeEvents $event
   *   The event to process.
   */
  public function onPurgeCreation(AkamaiPurgeEvents $event) {
    $this->event = $event;
    $event->data[] = 'on_purge_creation';
  }

}
