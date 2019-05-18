<?php
/**
 * @file
 * Contains \Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface.
 */

namespace Drupal\monitoring\SensorPlugin;

use Drupal\monitoring\Result\SensorResultInterface;

/**
 * Interface for a sensor with extended info.
 *
 * Implemented by sensors with verbose information.
 */
interface ExtendedInfoSensorPluginInterface {

  /**
   * Provide additional info about sensor call.
   *
   * This method is only executed on request. It is guaranteed that runSensor()
   * is executed before this method.
   *
   * @param \Drupal\monitoring\Result\SensorResultInterface $result
   *   Sensor result.
   *
   * @return array
   *   Sensor call verbose info as render array.
   */
  function resultVerbose(SensorResultInterface $result);

}
