<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\CronLastRunAgeSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;

/**
 * Monitors the last cron run time.
 *
 * @SensorPlugin(
 *   id = "cron_last_run_time",
 *   label = @Translation("Cron Last Run Age"),
 *   description = @Translation("Monitors the last cron run time."),
 *   addable = FALSE
 * )
 *
 * Based on the drupal core system state cron_last.
 */
class CronLastRunAgeSensorPlugin extends SensorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    $last_cron_run_before = \Drupal::time()->getRequestTime() - \Drupal::state()->get('system.cron_last');
    $result->setValue($last_cron_run_before);
  }
}
