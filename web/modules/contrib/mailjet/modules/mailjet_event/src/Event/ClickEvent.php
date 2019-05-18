<?php

namespace Drupal\mailjet_event\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Click event is created for Mailjet event module.
 *
 */
class ClickEvent extends GenericEvent {

  const EVENT_NAME = 'click_mailjet_event';

  const SUBMIT = 'event.submit';

  protected $event_var;

  public function __construct($event_var) {
    $this->event_var = $event_var;
  }

  public function getClickEvent() {
    return $this->event_var;
  }

}
