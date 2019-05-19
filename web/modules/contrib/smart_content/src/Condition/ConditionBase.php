<?php

namespace Drupal\smart_content\Condition;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\smart_content\Condition\ConditionInterface;
use Drupal\smart_content\ConditionSource\ConditionGroupInterface;
use Drupal\smart_content\ConditionType\ConditionTypeInterface;

/**
 * Base class for Smart condition plugins.
 */
abstract class ConditionBase extends PluginBase implements ConditionInterface, ConfigurablePluginInterface {

  /**
   * Configuration storage.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Gets ID for condition.
   *
   * @return string|null
   */
  public function id() {
    return isset($this->configuration['id']) ? $this->configuration['id'] : NULL;
  }

  /**
   * Sets ID of condition.
   *
   * @param string $id
   */
  public function setId($id) {
    $configuration = $this->getConfiguration();
    $configuration['id'] = $id;
    $this->setConfiguration($configuration);
  }

  /**
   * Gets weight of condition.
   *
   * @return int
   */
  public function getWeight() {
    return isset($this->configuration['weight']) ? $this->configuration['weight'] : 0;
  }

  /**
   * Returns array of libraries required to claim and satisfy condition.
   *
   * @return array
   */
  public function getLibraries() {
    return [];
  }

  /**
   * Returns array of settings that will be attached to page on render for
   * processing conditions.
   *
   * @return array
   */
  public function getAttachedSettings() {
    $definition = $this->getPluginDefinition();
    $settings = [
      'field' => [
        'pluginId' => $this->getPluginId(),
        'unique' => $definition['unique'],
      ],
    ];
    return $settings;
  }

  /**
   * Utility function to provide "If/If not" select element.
   *
   * @param $form
   * @param $config
   *
   * @return mixed
   */
  public static function attachNegateElement($form, $config) {
    $form['negate'] = [
      '#title' => 'Negate',
      '#title_display' => 'hidden',
      '#type' => 'select',
      '#default_value' => isset($config['negate']) ? $config['negate'] : FALSE,
      '#empty_option' => 'If',
      '#empty_value' => FALSE,
      '#options' => [TRUE => 'If Not'],
    ];
    return $form;
  }

  /**
   * Sets Weight of condition.
   *
   * @param int $weight
   */
  public function setWeight($weight) {
    $configuration = $this->getConfiguration();
    $configuration['weight'] = $weight;
    $this->setConfiguration($configuration);
  }

  /**
   * @inheritdoc
   */
  public function defaultConfiguration() {
    return [
      'id' => $this->id(),
      'plugin_id' => $this->getPluginId(),
      'weight' => $this->getWeight(),
    ];
  }

  /**
   * @inheritdoc
   */
  public function calculateDependencies() {
    // TODO: Implement calculateDependencies() method.
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = $configuration + $this->defaultConfiguration();
  }
  
}
