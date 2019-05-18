<?php

namespace Drupal\log_entity;

use Drupal\log_entity\Event\LogEvent;

/**
 * The event logger interface.
 */
interface EventLoggerInterface {

  /**
   * Persist the event.
   *
   * @param \Drupal\log_entity\Event\LogEvent $event
   *   The event itself.
   */
  public function logEvent(LogEvent $event);

}
