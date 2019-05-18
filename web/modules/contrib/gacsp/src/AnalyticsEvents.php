<?php

namespace Drupal\gacsp;

/**
 * Contains all events thrown by gacsp module.
 */
final class AnalyticsEvents {

  /**
   * Name of event fired to collect analytics commands for the current request.
   *
   * The event listener receives a \Drupal\gacsp\Event\CollectEvent instance.
   *
   * @Event
   *
   * @var string
   */
  const COLLECT = 'gacsp.collect';

}
