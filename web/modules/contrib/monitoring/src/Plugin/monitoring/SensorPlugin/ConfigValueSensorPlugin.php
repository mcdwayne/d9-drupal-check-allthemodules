<?php

/**
 * @file
 * Contains \Drupal\monitoring\Plugin\monitoring\SensorPlugin\ConfigValueSensorPlugin
 */

namespace Drupal\monitoring\Plugin\monitoring\SensorPlugin;

use Drupal\Core\Entity\DependencyTrait;
use Drupal\Core\Form\FormStateInterface;
use Drupal\monitoring\SensorPlugin\ValueComparisonSensorPluginBase;

/**
 * Generic sensor that checks for a configuration value.
 *
 * @SensorPlugin(
 *   id = "config_value",
 *   label = @Translation("Config Value"),
 *   description = @Translation("Checks for a specific configuration value."),
 *   addable = TRUE
 * )
 */
class ConfigValueSensorPlugin extends ValueComparisonSensorPluginBase {

  use DependencyTrait;

  /**
   * {@inheritdoc}
   */
  protected function getValueDescription() {
    return (t('The expected value of config %key, current value: %actVal',
      array(
        '%key' => $this->sensorConfig->getSetting('config') . ':' . $this->sensorConfig->getSetting('key'),
        '%actVal' => $this->getValueText(),
      )));
  }

  /**
   * {@inheritdoc}
   */
  protected function getValue() {
    $config = $this->getConfig($this->sensorConfig->getSetting('config'));;
    $key = $this->sensorConfig->getSetting('key');
    if (empty($key)) {
      return NULL;
    }
    return $config->get($key);
  }

  /**
   * Gets config.
   *
   * @param string $name
   *   Config name.
   *
   * @return \Drupal\Core\Config\Config
   *   The config.
   */
  protected function getConfig($name) {
    // @todo fix condition $name==''
    return $this->getService('config.factory')->get($name);
  }

  /**
   * Adds UI for variables config object and key.
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    // Add weight to display config key before expected value.
    $form['config'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->sensorConfig->getSetting('config') ? $this->sensorConfig->getSetting('config') : '',
      '#autocomplete_route_name' => 'monitoring.config_autocomplete',
      '#maxlength' => 255,
      '#title' => t('Config Object'),
      '#required' => TRUE,
      '#weight' => -1,
    );
    $form['key'] = array(
      '#type' => 'textfield',
      '#default_value' => $this->sensorConfig->getSetting('key') ? $this->sensorConfig->getSetting('key') : '',
      '#maxlength' => 255,
      '#title' => t('Key'),
      '#required' => TRUE,
      '#weight' => -1,
    );
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    $config_object = $this->sensorConfig->getSetting('config');
    $config = \Drupal::config($config_object);
    if (isset($config) && isset($config->getOriginal()['dependencies'])) {
      $this->addDependency('config', $config_object);
    }
    $this->addDependency('module', strtok($config_object, '.'));
    return $this->dependencies;
  }

}
