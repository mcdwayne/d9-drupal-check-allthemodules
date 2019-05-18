<?php

namespace Drupal\mailjet_event\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Open event is created for Mailjet event module.
 *
 */
class OpenEvent extends GenericEvent {

  const EVENT_NAME = 'open_event';
  const SUBMIT = 'event.submit';

  protected $event_var;

  public function __construct($event_var) {
    $this->event_var = $event_var;
  }

  public function getOpenEvent() {
    return $this->event_var;
  }

}
