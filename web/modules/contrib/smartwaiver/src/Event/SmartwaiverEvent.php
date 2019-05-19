<?php

namespace Drupal\smartwaiver\Event;

use Symfony\Component\EventDispatcher\Event;

class SmartwaiverEvent extends Event {

  const NEW_WAIVER = 'smartwaiver.new_waiver';

  /**
   * The waiver object returned by the SmartWaiver API.
   *
   * @var \StdClass
   */
  protected $waiver;

  /**
   * Creates a new smartwaiver event.
   *
   * @param \StdClass $waiver
   *   A waiver object returned by the SmartWaiver API.
   */
  public function __construct(\StdClass $waiver) {
    $this->waiver = $waiver;
  }

  /**
   * Retrieves the participants from the waiver object.
   *
   * @return \StdClass[]
   *   An array of the participant objects returned by the Smartwaiver API.
   */
  public function getParticipants() {
    return $this->waiver->participants;
  }

  /**
   * Return the current waiver object.
   *
   * @return \StdClass
   */
  public function getWaiver() {
    return $this->waiver;
  }

}
