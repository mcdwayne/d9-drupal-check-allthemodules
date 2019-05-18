<?php

namespace Drupal\monitoring\Entity;

/**
 * Interface for sensor data.
 *
 */
interface SensorResultDataInterface {

  /**
   * Sensor status OK.
   *
   * @var string
   */
  const STATUS_OK = 'OK';

  /**
   * Sensor status INFO.
   *
   * @var string
   */
  const STATUS_INFO = 'INFO';

  /**
   * Sensor status WARNING.
   *
   * @var string
   */
  const STATUS_WARNING = 'WARNING';

  /**
   * Sensor status CRITICAL.
   *
   * @var string
   */
  const STATUS_CRITICAL = 'CRITICAL';

  /**
   * Sensor status UNKNOWN.
   *
   * @var string
   */
  const STATUS_UNKNOWN = 'UNKNOWN';

  /**
   * Gets sensor status.
   *
   * @return string
   *   Sensor status.
   */
  public function getStatus();

  /**
   * Gets a human readable label for the sensor status.
   *
   * @return string
   *   Sensor status label.
   */
  public function getStatusLabel();

  /**
   * Gets the sensor metric value.
   *
   * @return mixed
   *   Whatever value the sensor is supposed to return.
   */
  public function getValue();

  /**
   * Gets sensor status message.
   *
   * Must not be called on an uncompiled result.
   *
   * @return string
   *   Sensor status message.
   */
  public function getMessage();

  /**
   * Get sensor execution time in ms.
   *
   * @return float
   */
  public function getExecutionTime();

  /**
   * The result data timestamp.
   *
   * @return int
   *   UNIX timestamp.
   */
  public function getTimestamp();

}
