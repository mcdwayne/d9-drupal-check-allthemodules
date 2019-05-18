<?php

namespace Drupal\mailjet_event\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Unsubscribe event is created for Mailjet event module.
 *
 */
class UnsubscribeEvent extends GenericEvent {

  const EVENT_NAME = 'unsubscribe_event';

  const SUBMIT = 'event.submit';

  protected $event_var;

  public function __construct($event_var) {
    $this->event_var = $event_var;
  }

  public function getUnSubEvent() {
    return $this->event_var;
  }

}
