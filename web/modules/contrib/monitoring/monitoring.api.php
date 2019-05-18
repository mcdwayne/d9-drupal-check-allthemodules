<?php
/**
 * @file
 * Monitoring API documentation.
 */

use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Allows to alter sensor links on the sensor overview page.
 *
 * @param array $links
 *   Links to be altered.
 * @param \Drupal\monitoring\Entity\SensorConfig $sensor_config
 *   Sensor config object of a sensor for which links are being altered.
 *
 * @see \Drupal\monitoring\Controller\SensorList::content()
 */
function hook_monitoring_sensor_links_alter(&$links, \Drupal\monitoring\Entity\SensorConfig $sensor_config) {

}

/**
 * Allows processing the result on each sensor run.
 *
 * @param \Drupal\monitoring\Result\SensorResultInterface[] $results
 *   The sensor results.
 */
function hook_monitoring_run_sensors(array $results) {

}
