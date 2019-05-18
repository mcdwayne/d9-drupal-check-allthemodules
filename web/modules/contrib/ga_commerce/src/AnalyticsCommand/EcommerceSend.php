<?php

namespace Drupal\ga_commerce\AnalyticsCommand;

use Drupal\ga\AnalyticsCommand\Generic;

/**
 * Defines the ecommerce:send command.
 */
class EcommerceSend extends Generic {

  const DEFAULT_PRIORITY = -10;

  /**
   * Constructs a new EcommerceSend object.
   *
   * @param string $tracker_name
   *   The tracker name (optional).
   * @param int $priority
   *   The command priority.
   */
  public function __construct($tracker_name = NULL, $priority = self::DEFAULT_PRIORITY) {
    parent::__construct('ecommerce:send', [], $tracker_name, $priority);
  }

}
