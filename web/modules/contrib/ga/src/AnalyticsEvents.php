<?php

namespace Drupal\ga;

/**
 * Contains all events thrown by ga module.
 */
final class AnalyticsEvents {

  /**
   * Name of event fired to collect analytics commands for the current request.
   *
   * The event listener receives a \Drupal\ga\Event\CollectEvent instance.
   *
   * @Event
   *
   * @var string
   */
  const COLLECT = 'ga.collect';

}
