<?php

namespace Drupal\drd\Agent\Remote\V8;

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
    $moduleManager = \Drupal::moduleHandler();

    $review = [];

    if ($moduleManager->moduleExists('monitoring')) {
      /* @var \Drupal\monitoring\Result\SensorResultInterface $result */
      foreach (monitoring_sensor_run_multiple() as $result) {
        $review[$result->getSensorId()] = $result->toArray();
        $review[$result->getSensorId()]['label'] = $result->getSensorConfig()->getLabel();
      }
    }

    return $review;
  }

}
