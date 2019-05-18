<?php

namespace Drupal\ga\AnalyticsCommand;

/**
 * Class Pageview.
 */
class Pageview extends Send {

  /*
   * Pageview event should come before other Send commands (e.g. event), but
   * after most other commands (e.g. set dimension)
   */
  const DEFAULT_PRIORITY = -5;

  /**
   * Create constructor.
   *
   * @param array $fields_object
   *   A map of values for the command's fieldsObject parameter.
   * @param string $tracker_name
   *   The tracker name (optional).
   * @param int $priority
   *   The command priority.
   */
  public function __construct(array $fields_object = [], $tracker_name = NULL, $priority = self::DEFAULT_PRIORITY) {
    parent::__construct('pageview', $fields_object, $tracker_name, $priority);
  }

}
