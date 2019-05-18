<?php

namespace Drupal\discourse_sync\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\discourse_sync\UserEvent;

/**
 * EventSubscriber class
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[UserEvent::EVENT][] = ['onUserCreate', 0];
    
    return $events;
  }
  
  /**
   * Handler for the discourse_sync user event.
   *
   * @param \Drupal\discourse_sync\UserEvent $event
   */
  public function onUserCreate(UserEvent $event) {
    $service = \Drupal::service('discourse_sync.role');
    $service->syncUserRoles($event->getUsername(), $event->getUserRoles());
  }
}
