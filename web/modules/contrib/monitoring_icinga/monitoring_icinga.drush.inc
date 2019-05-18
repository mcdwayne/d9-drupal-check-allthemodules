<?php

/**
 * @file
 * Drush integration for monitoring_icinga.module.
 */

use Drupal\Component\Utility\Unicode;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Sensor\DisabledSensorException;
use Drupal\monitoring\Sensor\NonExistingSensorException;

/**
 * Implements hook_drush_command().
 */
function monitoring_icinga_drush_command() {
  $items = [];

  $items['monitoring-icinga'] = [
    'callback' => 'monitoring_icinga_drush_callback',
    'options' => [
      'sensor_name' => 'Sensor name to invoke.',
    ],
    'description' => 'Run Icinga plugins.',
    'examples' => [
      'drush monitoring-icinga plugin_name' => 'Run Icinga plugin.',
    ],
    'drupal dependencies' => ['monitoring_icinga'],
  ];

  return $items;
}

/**
 * Drush callback to get the sensor info.
 *
 * @param string $sensor_name
 *   Sensor name.
 */
function monitoring_icinga_drush_callback($sensor_name = NULL) {
  // If we are provided with sensor name we expect an active check.
  if (!empty($sensor_name)) {
    monitoring_icinga_active_check($sensor_name);
    return;
  }

  $results = monitoring_sensor_run_multiple();
  $output = '';

  foreach ($results as $result) {
    /** @var \Drupal\monitoring\Result\SensorResultInterface $result */
    $message = iconv('utf-8', 'ASCII', Unicode::truncate(strip_tags($result->getMessage()), 124, TRUE, TRUE));
    $service_description = monitoring_icinga_service_description($result->getSensorConfig());

    // Map INFO status to OK.
    $status = $result->getStatus();
    if ($status == SensorResultInterface::STATUS_INFO) {
      $status = SensorResultInterface::STATUS_OK;
    }

    // Make sure the output does not have any new lines.
    $output .= preg_replace('/\s+/', ' ', $service_description . '|' . $status . '|' . $message) . '~';

  }
  drush_print($output, 0, NULL, FALSE);
}

/**
 * Prints sensor status message and exits with sensor status code.
 *
 * This callback should be used for active icinga checks where icinga directly
 * invokes the sensor and expects printed output and exit code.
 *
 * @param string $sensor_name
 *   Sensor name to run.
 */
function monitoring_icinga_active_check($sensor_name) {
  try {
    $result = monitoring_sensor_run($sensor_name);

    // Map INFO status to OK.
    $status = $result->getStatus();
    if ($status == SensorResultInterface::STATUS_INFO) {
      $status = SensorResultInterface::STATUS_OK;
    }

    drush_print(iconv('utf-8', 'ASCII', $status . ' - ' . $result->getMessage()));
  }
  catch (NonExistingSensorException $e) {
    drush_set_error('MONITORING_SENSOR_INVALID_NAME', dt('Sensor "@name" does not exist.', ['@name' => $sensor_name]));
  }
  catch (DisabledSensorException $e) {
    drush_set_error('MONITORING_SENSOR_DISABLED', dt('Sensor "@name" is not enabled.', ['@name' => $sensor_name]));
  }
}
