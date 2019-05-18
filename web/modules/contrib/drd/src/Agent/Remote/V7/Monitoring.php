<?php

namespace Drupal\drd\Agent\Remote\V7;

/**
 * Implements the Monitoring class.
 */
class Monitoring {

  /**
   * Collect the monitoring results.
   *
   * @return array
   *   List of all the monitoring results.
   */
  public static function collect() {
    $review = array();

    if (module_exists('monitoring')) {
      foreach (monitoring_sensor_run_multiple() as $result) {
        $review[$result->getSensorName()] = $result->toArray();
        $review[$result->getSensorName()]['label'] = $result->getSensorInfo()->getLabel();
      }
    }

    return $review;
  }

}
