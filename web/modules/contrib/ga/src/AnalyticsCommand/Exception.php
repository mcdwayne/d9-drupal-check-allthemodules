<?php

namespace Drupal\ga\AnalyticsCommand;

/**
 * Class Exception.
 */
class Exception extends Send {

  /**
   * Exception constructor.
   *
   * @param array $fields_object
   *   A map of values for the command's fieldsObject parameter.
   * @param string $tracker_name
   *   The tracker name (optional).
   * @param int $priority
   *   The command priority.
   */
  public function __construct(array $fields_object = [], $tracker_name = NULL, $priority = self::DEFAULT_PRIORITY) {
    parent::__construct('exception', $fields_object, $tracker_name, $priority);
  }

}
