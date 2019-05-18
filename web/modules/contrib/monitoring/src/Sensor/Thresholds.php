<?php
/**
 * @file
 * Contains \Drupal\monitoring\Sensor\Thresholds.
 */

namespace Drupal\monitoring\Sensor;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\monitoring\Result\SensorResultInterface;
use Drupal\monitoring\Entity\SensorConfig;

/**
 * Determine status based on thresholds during sensor run.
 *
 * @see \Drupal\monitoring\Result\SensorResult::assessThresholds()
 */
class Thresholds {

  /**
   * The SensorConfig instance.
   *
   * @var \Drupal\monitoring\Entity\SensorConfig
   */
  protected $sensorConfig;

  /**
   * The message that will be added to the result status message.
   *
   * @var string
   */
  protected $message;

  /**
   * Constructs a Thresholds object.
   *
   * @param \Drupal\monitoring\Entity\SensorConfig $sensor_config
   *   The SensorConfig instance.
   */
  function __construct(SensorConfig $sensor_config) {
    $this->sensorConfig = $sensor_config;
  }

  /**
   * Gets status based on given value.
   *
   * Note that if the threshold value is NULL or an empty string no assessment
   * will be carried out therefore the OK value will be returned.
   *
   * @param int $value
   *   The sensor value to assess.
   *
   * @return int
   *   The assessed sensor status.
   */
  public function getMatchedThreshold($value) {
    if (method_exists($this, $this->sensorConfig->getThresholdsType())) {
      $status = $this->{$this->sensorConfig->getThresholdsType()}($value);
      if ($status !== NULL) {
        return $status;
      }
      return SensorResultInterface::STATUS_OK;
    }
    else {
      $this->message = new FormattableMarkup('Unknown threshold type @type', array('@type' => $this->sensorConfig->getThresholdsType()));
      return SensorResultInterface::STATUS_CRITICAL;
    }
  }

  /**
   * Gets status message based on the status and threshold type.
   *
   * @return string
   *   Status message
   */
  public function getStatusMessage() {
    return $this->message;
  }

  /**
   * Checks if provided value exceeds the configured threshold.
   *
   * @param int $value
   *   The value to check.
   *
   * @return int|null
   *   A sensor status or NULL.
   */
  protected function exceeds($value) {
    if (($threshold = $this->sensorConfig->getThresholdValue('critical')) !== NULL && $value > $threshold) {
      $this->message = new FormattableMarkup('exceeds @expected', array('@expected' => $threshold));
      return SensorResultInterface::STATUS_CRITICAL;
    }
    if (($threshold = $this->sensorConfig->getThresholdValue('warning')) !== NULL && $value > $threshold) {
      $this->message = new FormattableMarkup('exceeds @expected', array('@expected' => $threshold));
      return SensorResultInterface::STATUS_WARNING;
    }
  }

  /**
   * Checks if provided value falls below the configured threshold.
   *
   * @param int $value
   *   The value to check.
   *
   * @return int|null
   *   A sensor status or NULL.
   */
  protected function falls($value) {
    if (($threshold = $this->sensorConfig->getThresholdValue('critical')) !== NULL && $value < $threshold) {
      $this->message = new FormattableMarkup('falls below @expected', array('@expected' => $threshold));
      return SensorResultInterface::STATUS_CRITICAL;
    }
    if (($threshold = $this->sensorConfig->getThresholdValue('warning')) !== NULL && $value < $threshold) {
      $this->message = new FormattableMarkup('falls below @expected', array('@expected' => $threshold));
      return SensorResultInterface::STATUS_WARNING;
    }
  }

  /**
   * Checks if provided value falls inside the configured interval.
   *
   * @param int $value
   *   The value to check.
   *
   * @return int|null
   *   A sensor status or NULL.
   */
  protected function inner_interval($value) {
    if (($low = $this->sensorConfig->getThresholdValue('critical_low')) !== NULL && ($high = $this->sensorConfig->getThresholdValue('critical_high')) !== NULL) {
      if ($value > $low && $value < $high) {
        $this->message = new FormattableMarkup('violating the interval @low - @high', array('@low' => $low, '@high' => $high));
        return SensorResultInterface::STATUS_CRITICAL;
      }
    }
    if (($low = $this->sensorConfig->getThresholdValue('warning_low')) !== NULL && ($high = $this->sensorConfig->getThresholdValue('warning_high')) !== NULL) {
      if ($value > $low && $value < $high) {
        $this->message = new FormattableMarkup('violating the interval @low - @high', array('@low' => $low, '@high' => $high));
        return SensorResultInterface::STATUS_WARNING;
      }
    }
  }

  /**
   * Checks if provided value is outside of the configured interval.
   *
   * @param int $value
   *   The value to check.
   *
   * @return int|null
   *   A sensor status or NULL.
   */
  protected function outer_interval($value) {
    if (($low = $this->sensorConfig->getThresholdValue('critical_low')) !== NULL && ($high = $this->sensorConfig->getThresholdValue('critical_high')) !== NULL) {
      if ($value < $low || $value > $high) {
        $this->message = new FormattableMarkup('outside the allowed interval @low - @high', array('@low' => $low, '@high' => $high));
        return SensorResultInterface::STATUS_CRITICAL;
      }
    }
    if (($low = $this->sensorConfig->getThresholdValue('warning_low')) !== NULL && ($high = $this->sensorConfig->getThresholdValue('warning_high')) !== NULL) {
      if ($value < $low || $value > $high) {
        $this->message = new FormattableMarkup('outside the allowed interval @low - @high', array('@low' => $low, '@high' => $high));
        return SensorResultInterface::STATUS_WARNING;
      }
    }
  }

  /**
   * Returns the sensor status ok for the default threshold type None.
   *
   * @return string
   *   The sensor status.
   */
  protected function none() {
    return SensorResultInterface::STATUS_OK;
  }
}
