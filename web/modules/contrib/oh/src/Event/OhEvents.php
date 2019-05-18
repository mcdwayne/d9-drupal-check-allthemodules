<?php

namespace Drupal\oh\Event;

/**
 * Defines events for opening hours.
 */
final class OhEvents {

  /**
   * Used to add regular hours between a date range.
   *
   * @Event
   *
   * @see \Drupal\oh\Event\OhRegularEvent
   */
  const REGULAR = 'oh.regular';

  /**
   * Used to add exceptions between a date range.
   *
   * @Event
   *
   * @see \Drupal\oh\Event\OhExceptionEvent
   */
  const EXCEPTIONS = 'oh.exceptions';

}
