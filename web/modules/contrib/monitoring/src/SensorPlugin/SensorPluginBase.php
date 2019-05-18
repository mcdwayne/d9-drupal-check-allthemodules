<?php
/**
 * @file
 * Contains \Drupal\monitoring\SensorPlugin\SensorPluginBase.
 */

namespace Drupal\monitoring\SensorPlugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\monitoring\Entity\SensorConfig;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Abstract SensorPluginInterface implementation with common behaviour and will be extended by
 * sensor plugins.
 *
 * @todo more
 */
abstract class SensorPluginBase implements SensorPluginInterface {

  use StringTranslationTrait;

  /**
   * Current sensor config object.
   *
   * @var \Drupal\monitoring\Entity\SensorConfig
   */
  protected $sensorConfig;
  protected $services = array();

  /**
   * The plugin_id.
   *
   * @var string
   */
  protected $pluginId;

  /**
   * The plugin implementation definition.
   *
   * @var array
   */
  protected $pluginDefinition;

  /**
   * Allows plugins to control if the value type can be configured.
   *
   * @var bool
   */
  protected $configurableValueType = TRUE;


  /**
   * Instantiates a sensor object.
   *
   * @param \Drupal\monitoring\Entity\SensorConfig $sensor_config
   *   Sensor config object.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   */
  function __construct(SensorConfig $sensor_config, $plugin_id, $plugin_definition) {
    $this->pluginId = $plugin_id;
    $this->pluginDefinition = $plugin_definition;
    $this->sensorConfig = $sensor_config;
  }

  /**
   * {@inheritdoc}
   */
  public function addService($id, $service) {
    $this->services[$id] = $service;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurableValueType() {
    return $this->configurableValueType;
  }

  /**
   * {@inheritdoc}
   *
   * @todo: Replace with injection
   */
  public function getService($id) {
    return \Drupal::service($id);
  }

  /**
   * {@inheritdoc}
   */
  public function getSensorId() {
    return $this->sensorConfig->id();
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return (boolean) $this->sensorConfig->isEnabled();
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginId() {
    return $this->pluginId;
  }

  /**
   * {@inheritdoc}
   */
  public function getPluginDefinition() {
    return $this->pluginDefinition;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, SensorConfig $sensor_config, $plugin_id, $plugin_definition) {
    return new static(
      $sensor_config,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Do nothing.
  }

}
