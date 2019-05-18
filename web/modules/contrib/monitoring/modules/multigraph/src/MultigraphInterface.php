<?php
/**
 * @file
 * Contains \Drupal\monitoring_multigraph\MultigraphInterface.
 */

namespace Drupal\monitoring_multigraph;
use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Interface defining an aggregation of related sensors, called a multigraph.
 *
 * A multigraph can be read like a sensor, but its result is calculated directly
 * from the included sensors.
 */
interface MultigraphInterface extends ConfigEntityInterface {
  /**
   * Excludes a sensor that has previously been included.
   *
   * @param string $name
   *   Machine name of included sensor.
   */
  public function removeSensor($name);

  /**
   * Includes a sensor.
   *
   * @param string $name
   *   The machine name of the sensor that should be included by the multigraph.
   * @param string $label
   *   (optional) Custom label for the sensor within the multigraph.
   */
  public function addSensor($name, $label = NULL);

  /**
   * Returns the included sensors as stored in config.
   *
   * @return array[]
   *   An associative array where keys are sensor IDs and values are associative
   *   arrays containing:
   *     - weight
   *     - label: custom sensor label for this multigraph
   */
  public function getSensorsRaw();

  /**
   * Gets the included sensors.
   *
   * @return \Drupal\monitoring\Entity\SensorConfig[]
   *   The included sensors as an indexed array sorted by weight where the
   *   values are sensors with custom labels.
   */
  public function getSensors();

  /**
   * Gets the multigraph description.
   *
   * @return string
   *   Sensor description.
   */
  public function getDescription();

  /**
   * Compiles sensor values to an associative array.
   *
   * @return array
   *   Sensor config associative array.
   */
  public function getDefinition();

}
