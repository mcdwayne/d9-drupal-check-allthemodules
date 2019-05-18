<?php

namespace Drupal\mailjet_event\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Spam event is created for Mailjet event module.
 *
 */
class SpamEvent extends GenericEvent {

  const EVENT_NAME = 'spam_event';
  const SUBMIT = 'event.submit';

  protected $event_var;

  public function __construct($event_var) {
    $this->event_var = $event_var;
  }

  public function getSpamEvent() {
    return $this->event_var;
  }

}
