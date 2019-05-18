<?php
/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\ElysiaCronSensorPlugin.
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\SensorPlugin\SensorPluginBase;

/**
 * Monitors elysia cron channels for last execution.
 *
 * @SensorPlugin(
 *   id = "elysia_cron",
 *   label = @Translation("Elysia Cron"),
 *   description = @Translation("Monitors elysia cron channels for last execution."),
 *   provider = "elysia_cron",
 *   addable = FALSE
 * )
 *
 */
class ElysiaCronSensorPlugin extends SensorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function runSensor(SensorResultInterface $result) {
    // The channel name.
    $name = $this->sensorConfig->getSetting('name');
    $query = \Drupal::database()->select('elysia_cron', 'e')->fields('e', array($this->sensorConfig->getSetting('metric')));
    $query->condition('name', $name);

    $value = $query->execute()->fetchField();

    // In case we are querying for last_run, the value is the seconds ago.
    if ($this->sensorConfig->getSetting('metric') == 'last_run') {
      $value = REQUEST_TIME - $value;
      $result->addStatusMessage('@time ago', array('@time' => \Drupal::service('date.formatter')->formatInterval($value)));
    }
    else {
      // metric last_execution_time
      $result->addStatusMessage('at @time', array('@time' => format_date($value)));
    }

    $result->setValue($value);
  }
}
