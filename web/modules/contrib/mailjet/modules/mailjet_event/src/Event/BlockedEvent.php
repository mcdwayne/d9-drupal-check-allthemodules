<?php

namespace Drupal\mailjet_event\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Block event is created for Mailjet event module.
 *
 */
class BlockedEvent extends GenericEvent {

  const EVENT_NAME = 'blocked_event';

  const SUBMIT = 'event.submit';

  protected $event_var;

  public function __construct($event_var) {
    $this->event_var = $event_var;
  }

  public function getBlockedEvent() {
    return $this->event_var;
  }


}
