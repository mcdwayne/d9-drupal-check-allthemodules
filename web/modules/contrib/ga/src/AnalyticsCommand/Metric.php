<?php

namespace Drupal\ga\AnalyticsCommand;

/**
 * Class Dimension.
 *
 * @package Drupal\ga\AnalyticsCommand
 */
class Metric extends Set {

  /**
   * Dimension constructor.
   *
   * @param int $index
   *   The metric index.
   * @param int|float $value
   *   The metric value.
   * @param array $fields_object
   *   A set of additional options for the command.
   * @param string $tracker_name
   *   The tracker name.
   * @param int $priority
   *   The command priority.
   */
  public function __construct($index, $value, array $fields_object = [], $tracker_name = NULL, $priority = self::DEFAULT_PRIORITY) {

    // TODO remove this cast in favour of typing in PHP7.
    if (!is_int($index)) {
      if (!is_string($index) || !ctype_digit($index)) {
        throw new \InvalidArgumentException("Metric index must be an integer between 0 and 199");
      }
      $index = (int) $index;
    }

    if ($index < 0 || $index >= 200) {
      throw new \InvalidArgumentException("Metric index must be an integer between 0 and 199");
    }

    if (!is_int($value) || !is_float($value)) {
      if (!is_numeric($value)) {
        throw new \InvalidArgumentException("Metric value must be numeric");
      }

      if (ctype_digit($value)) {
        $value = (int) $value;
      }
      else {
        $value = (float) $value;
      }
    }

    parent::__construct('metric' . $index, $value, $fields_object, $tracker_name, $priority);
  }

}
