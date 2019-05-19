<?php

namespace Drupal\simple_analytics;

use Symfony\Component\EventDispatcher\Event;

/**
 * Simple Analytics Events.
 */
class SimpleAnalyticsEvents extends Event {

  /**
   * Simple analytics tracking event.
   */
  const TRACK = 'simple_analytics.track';

  /**
   * Simple analytics tracking data.
   *
   * @var mixed
   */
  protected $data;

  /**
   * Build Simple analytics tracking event.
   */
  public function __construct($data) {
    $this->data = $data;
  }

  /**
   * Get Simple analytics tracking data.
   */
  public function getData() {
    return $this->data;
  }

}
