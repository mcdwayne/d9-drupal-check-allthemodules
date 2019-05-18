<?php

namespace Drupal\og_sm_path\EventSubscriber;

use Drupal\og_sm_path\Event\AjaxPathEvent;
use Drupal\og_sm_path\Event\AjaxPathEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Listens to events.
 */
class EventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[AjaxPathEvents::COLLECT][] = 'onAjaxPathCollection';
    return $events;
  }

  /**
   * Event listener triggered ajax path are collected.
   *
   * @param \Drupal\og_sm_path\Event\AjaxPathEvent $event
   *   The ajax path event.
   */
  public function onAjaxPathCollection(AjaxPathEvent $event) {
    $event->addPath('/entity_reference_autocomplete/.*/.*/.*');
  }

}
