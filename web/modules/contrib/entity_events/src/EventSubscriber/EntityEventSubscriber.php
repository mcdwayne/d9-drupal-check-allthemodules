<?php

namespace Drupal\entity_events\EventSubscriber;

use Drupal\entity_events\EntityEventType;
use Drupal\entity_events\Event\EntityEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for entity delete event.
 */
abstract class EntityEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[EntityEventType::INSERT][] = ['onEntityInsert', 800];
    $events[EntityEventType::UPDATE][] = ['onEntityUpdate', 800];
    $events[EntityEventType::DELETE][] = ['onEntityDelete', 800];
    return $events;
  }

  /**
   * Method called when entity is created.
   *
   * @param \Drupal\entity_events\Event\EntityEvent $event
   *   The event.
   */
  public function onEntityInsert(EntityEvent $event) {

  }

  /**
   * Method called when entity is updated.
   *
   * @param \Drupal\entity_events\Event\EntityEvent $event
   *   The event.
   */
  public function onEntityUpdate(EntityEvent $event) {

  }

  /**
   * Method called when entity is deleted.
   *
   * @param \Drupal\entity_events\Event\EntityEvent $event
   *   The event.
   */
  public function onEntityDelete(EntityEvent $event) {

  }

}
