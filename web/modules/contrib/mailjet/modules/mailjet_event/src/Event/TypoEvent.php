<?php

namespace Drupal\mailjet_event\Event;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Typo event is created for Mailjet event module.
 *
 */
class TypoEvent extends GenericEvent {

  const EVENT_NAME = 'typo_event';

  const SUBMIT = 'event_typo.submit';

  protected $event_var;

  public function __construct($event_var) {
    $this->event_var = $event_var;
  }

  public function getTypoEvent() {
    return $this->event_var;
  }

}
