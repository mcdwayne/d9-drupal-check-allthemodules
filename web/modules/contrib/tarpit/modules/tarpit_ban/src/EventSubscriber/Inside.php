<?php

namespace Drupal\tarpit_ban\EventSubscriber;

use Drupal\tarpit\Event\InsideEvent;
use Drupal\tarpit_ban\Event\ReactionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Inside implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['tarpit.inside'][] = array('insideEvent', 0);
    return $events;
  }

  /**
   * Custom callback.
   *
   * @param $event InsideEvent
   *   The event.
   */
  public function insideEvent($event) {
    $depth = \Drupal::config('tarpit.config')->get('depth');
    $path = trim(\Drupal::request()->getPathInfo(), '/');

    if (count(explode('/', $path))-1 >= $depth) {
      $event = new ReactionEvent('subject');
      $event_dispatcher = \Drupal::service('event_dispatcher');
      $event_dispatcher->dispatch(ReactionEvent::EVENT_NAME, $event);
    }
  }

}
