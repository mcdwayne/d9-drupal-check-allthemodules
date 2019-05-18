<?php

namespace Drupal\mailjet_event\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Bounce event is created for Mailjet event module.
 *
 */
class BounceEvent extends GenericEvent {

  const EVENT_NAME = 'bounce_event';
  const SUBMIT = 'event.submit';

  protected $event_var;

  public function __construct($event_var) {
    $this->event_var = $event_var;
  }

  public function getBouncedEvent() {
    return $this->event_var;
  }

}
