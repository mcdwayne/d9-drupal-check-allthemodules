<?php
/**
 * @file
 * Contains \Drupal\monitoring\Entity\SensorConfig.
 */

namespace Drupal\monitoring\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\monitoring\SensorConfigInterface;

/**
 * Represents a sensor config entity class.
 *
 * @todo more
 *
 * @ConfigEntityType(
 *   id = "monitoring_sensor_config",
 *   label = @Translation("Monitoring Sensor"),
 *   handlers = {
 *     "access" = "Drupal\monitoring\SensorConfigAccessControlHandler",
 *     "list_builder" = "Drupal\monitoring\SensorListBuilder",
 *     "form" = {
 *       "add" = "Drupal\monitoring\Form\SensorForm",
 *       "delete" = "Drupal\monitoring\Form\SensorDeleteForm",
 *       "edit" = "Drupal\monitoring\Form\SensorForm",
 *       "details" = "Drupal\monitoring\Form\SensorDetailForm"
 *     }
 *   },
 *   admin_permission = "administer monitoring",
 *   config_prefix = "sensor_config",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *     "category",
 *     "plugin_id",
 *     "result_class",
 *     "value_label",
 *     "value_type",
 *     "status",
 *     "caching_time",
 *     "settings",
 *     "thresholds",
 *   },
 *   links = {
 *     "delete-form" = "/admin/config/system/monitoring/sensors/{monitoring_sensor_config}/delete",
 *     "edit-form" = "/admin/config/system/monitoring/sensors/{monitoring_sensor_config}",
 *     "details-form" = "/admin/reports/monitoring/sensors/{monitoring_sensor_config}",
 *     "force-run-sensor" = "/monitoring/sensors/force/{monitoring_sensor_config}"
 *   }
 * )
 */
class SensorConfig extends ConfigEntityBase implements SensorConfigInterface {

  /**
   * The config id.
   *
   * @var string
   */
  public $id;

  /**
   * The sensor label.
   *
   * @var string
   */
  public $label;

  /**
   * The sensor description.
   *
   * @var string
   */
  public $description = '';

  /**
   * The sensor category.
   *
   * @var string
   */
  public $category = 'Other';

  /**
   * The sensor id.
   *
   * @var string
   */
  public $plugin_id;

  /**
   * The sensor result class.
   *
   * @var string
   */
  public $result_class;

  /**
   * The sensor settings.
   *
   * @var array
   */
  public $settings = array();

  /**
   * The sensor value label.
   *
   * @var string
   */
  public $value_label;

  /**
   * The sensor value type.
   *
   * @var string
   */
  public $value_type = 'number';

  /**
   * The sensor caching time.
   *
   * @var integer
   */
  public $caching_time;

  /**
   * The sensor enabled/disabled flag.
   *
   * @var bool
   */
  public $status = TRUE;

  /**
   * The sensor thresholds.
   *
   * @var array
   */
  public $thresholds = array(
    'type' => 'none',
  );

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->label;
  }
  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->description;
  }

  /**
   * {@inheritdoc}
   */
  public function getSensorClass() {
    $definition = monitoring_sensor_manager()->getDefinition($this->plugin_id);
    return $definition['class'];
  }

  /**
   * {@inheritdoc}
   */
  public function getPlugin() {
    $configuration = array('sensor_config' => $this);
    $plugin = monitoring_sensor_manager()->createInstance($this->plugin_id, $configuration);
    return $plugin;
  }

  /**
   * {@inheritdoc}
   */
  public function getCategory() {
    return $this->category;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueLabel() {
    if ($this->value_label) {
      return $this->value_label;
    }
    if ($this->value_type) {
      $value_types = monitoring_value_types();
      if (isset($value_types[$this->value_type]['value_label'])) {
        return $value_types[$this->value_type]['value_label'];
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueType() {
    return $this->value_type;
  }

  /**
   * {@inheritdoc}
   */
  public function isNumeric() {
    $value_types = monitoring_value_types();
    if (empty($this->value_type)) {
      return FALSE;
    }
    return $value_types[$this->value_type]['numeric'];
  }

  /**
   * {@inheritdoc}
   */
  public function isBool() {
    return $this->getValueType() == 'bool';
  }

  /**
   * {@inheritdoc}
   */
  public function getCachingTime() {
    return $this->caching_time;
  }

  /**
   * {@inheritdoc}
   */
  public function getThresholdsType() {
    if (!empty($this->thresholds['type'])) {
      return $this->thresholds['type'];
    }

    return 'none';
  }

  /**
   * {@inheritdoc}
   */
  public function getThresholdValue($key) {
    if (isset($this->thresholds[$key]) && $this->thresholds[$key] !== '') {
      return $this->thresholds[$key];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    return $this->settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getThresholds() {
    return $this->thresholds;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimeIntervalValue() {
    return $this->getSetting('time_interval_value', NULL);
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($key, $default = NULL) {
    return isset($this->settings[$key]) ? $this->settings[$key] : $default;
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (boolean) $this->status;
  }

  /**
   * {@inheritdoc}
   */
  public function isExtendedInfo() {
    return in_array('Drupal\monitoring\SensorPlugin\ExtendedInfoSensorPluginInterface', class_implements($this->getSensorClass()));
  }

  /**
   * {@inheritdoc}
   */
  public function isDefiningThresholds() {
    return $this->isNumeric() && $this->getThresholdsType() != 'none';
  }

  /**
   * {@inheritdoc}
   */
  public function getDefinition() {
    $config = array(
      'sensor' => $this->id(),
      'label' => $this->getLabel(),
      'category' => $this->getCategory(),
      'description' => $this->getDescription(),
      'numeric' => $this->isNumeric(),
      'value_label' => $this->getValueLabel(),
      'caching_time' => $this->getCachingTime(),
      'time_interval' => $this->getTimeIntervalValue(),
      'enabled' => $this->isEnabled(),
    );

    if ($this->isDefiningThresholds()) {
      $config['thresholds'] = $this->getThresholds();
    }

    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public static function sort(ConfigEntityInterface $a, ConfigEntityInterface $b) {
    /**
     * @var \Drupal\monitoring\Entity\SensorConfig $a
     * @var \Drupal\monitoring\Entity\SensorConfig $b
     */
    // Checks whether both labels and categories are equal.
    if ($a->getLabel() == $b->getLabel() && $a->getCategory() == $b->getCategory()) {
      return 0;
    }
    // If the categories are not equal, their order is determined.
    elseif ($a->getCategory() != $b->getCategory()) {
      return ($a->getCategory() < $b->getCategory()) ? -1 : 1;
    }
    // In the end, the label's order is determined.
    return ($a->getLabel() < $b->getLabel()) ? -1 : 1;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    parent::calculateDependencies();
    // Include the module of the sensor plugin as dependency and also allow it
    // to add additional dependencies based on the configuration.
    $instance = $this->getPlugin();
    $definition = $instance->getPluginDefinition();
    $this->addDependency('module', $definition['provider']);
    // If a plugin is configurable, calculate its dependencies.
    if ($plugin_dependencies = $instance->calculateDependencies()) {
      $this->addDependencies($plugin_dependencies);
    }
    return $this->dependencies;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    \Drupal::service('monitoring.sensor_runner')->resetCache(array($this->id));
  }

}
