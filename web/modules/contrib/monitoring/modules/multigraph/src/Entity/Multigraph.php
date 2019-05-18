<?php
/**
 * @file
 * Contains \Drupal\monitoring_multigraph\Entity\Multigraph.
 */

namespace Drupal\monitoring_multigraph\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\monitoring\Entity\SensorConfig;
use Drupal\monitoring_multigraph\MultigraphInterface;

/**
 * Represents an aggregation of related sensors, called a multigraph.
 *
 * A multigraph can be read like a sensor, but its result is calculated directly
 * from the included sensors.
 *
 * @ConfigEntityType(
 *   id = "monitoring_multigraph",
 *   label = @Translation("Multigraph"),
 *   handlers = {
 *     "list_builder" = "\Drupal\monitoring_multigraph\MultigraphListBuilder",
 *     "form" = {
 *       "add" = "\Drupal\monitoring_multigraph\Form\MultigraphForm",
 *       "edit" = "\Drupal\monitoring_multigraph\Form\MultigraphForm",
 *       "delete" = "\Drupal\monitoring_multigraph\Form\MultigraphDeleteForm"
 *     }
 *   },
 *   admin_permission = "administer monitoring",
 *   config_prefix = "multigraph",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "sensors",
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/system/monitoring/multigraphs/{monitoring_multigraph}/delete",
 *     "edit-form" = "/admin/config/system/monitoring/multigraphs/{monitoring_multigraph}"
 *   }
 * )
 */
class Multigraph extends ConfigEntityBase implements MultigraphInterface {

  /**
   * The config id.
   *
   * @var string
   */
  protected $id;

  /**
   * The multigraph label.
   *
   * @var string
   */
  protected $label;

  /**
   * The multigraph description.
   *
   * @var string
   */
  protected $description = '';

  /**
   * The included sensors.
   *
   * This is an associative array, where keys are sensor machine names and each
   * value contains:
   *   - weight: the sensor weight for this multigraph
   *   - label: custom sensor label for the multigraph
   *
   * @var string[]
   */
  protected $sensors = array();

  /**
   * The entities of the included sensors, sorted by weight and with labels
   * overridden.
   *
   * @var \Drupal\monitoring\Entity\SensorConfig[]
   */
  protected $sensorEntities = array();

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    return array(
      'id' => $this->id(),
      'label' => $this->label(),
      'description' => $this->getDescription(),
      'sensors' => $this->sensors,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getSensorsRaw() {
    return $this->sensors;
  }

  /**
   * {@inheritdoc}
   */
  public function getSensors() {
    if (!empty($this->sensorEntities)) {
      return $this->sensorEntities;
    }

    foreach ($this->sensors as $name => $sensor_config) {
      $this->addSensorEntity($name, $sensor_config['label']);
    }
    return $this->sensorEntities;
  }

  /**
   * {@inheritdoc}
   */
  public function addSensor($name, $label = NULL) {
    $this->sensors[$name] = array(
      'label' => $label,
      'weight' => $this->sensors ? 1 + max(array_map(
        function ($mapping) {return $mapping['weight'];},
        $this->sensors
      )) : 0,
    );

    $this->addSensorEntity($name, $label);
  }

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage) {
    // Sort sensors by weight before saving.
    uasort($this->sensors, function($a, $b) {
      return $a['weight'] > $b['weight'];
    });
    parent::preSave($storage);
  }

  /**
   * {@inheritdoc}
   */
  public function removeSensor($name) {
    unset($this->sensors[$name]);
    foreach ($this->sensorEntities as $key => $entity) {
      if ($entity->id() == $name) {
        unset($this->sensorEntities[$key]);
        break;
      }
    }
  }

  /**
   * Loads the entity of a sensor and adds it to the end of the internal array.
   *
   * @param string $name
   *   Sensor machine name.
   * @param string $label
   *   (optional) Custom sensor label for this Multigraph.
   */
  protected function addSensorEntity($name, $label = NULL) {
    $sensor = \Drupal::entityManager()->getStorage('monitoring_sensor_config')->load($name);
    if (!empty($label)) {
      $sensor->label = $label;
    }
    $this->sensorEntities[] = $sensor;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array('entity' => array_map(function(SensorConfig $sensor) {
      return $sensor->getConfigDependencyName();
    }, $this->getSensors()));
  }

}
