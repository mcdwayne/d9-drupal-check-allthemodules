<?php

namespace Drupal\log_entity\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * The log event.
 */
abstract class LogEvent extends Event {

  /**
   * Event message.
   *
   * @var string
   */
  protected $message;

  /**
   * Event type.
   *
   * @var string
   */
  protected $eventType;

  /**
   * LogEvent constructor.
   *
   * @param string $event_type
   *   THe event type.
   */
  public function __construct($event_type) {
    $this->eventType = $event_type;
  }

  /**
   * Any arbitrary string to represent the even type.
   *
   * @return string
   *   The event type.
   */
  public function getEventType() {
    return $this->eventType;
  }

  /**
   * @return array
   *   An array of information representing the event.
   */
  public function toArray() {
    return [
      'event_type' => $this->getEventType(),
      'description' => $this->getDescription(),
    ];
  }

  /**
   * Gets the event description.
   *
   * @return string
   *   The description.
   */
  abstract function getDescription();

}
