<?php

namespace Drupal\feeds_test_events\EventSubscriber;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\EntityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * React on authors being processed.
 */
class FeedsSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[FeedsEvents::PROCESS_ENTITY_PREVALIDATE][] = 'prevalidate';
    return $events;
  }

  /**
   * Acts on an entity before validation.
   */
  public function prevalidate(EntityEvent $event) {
    $feed_type_id = $event->getFeed()->getType()->id();
    switch ($feed_type_id) {
      case 'no_title':
        // A title is required, set a title on the entity to prevent validation
        // errors.
        $event->getEntity()->title = 'foo';
        break;
    }
  }

}
