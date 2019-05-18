<?php

namespace Drupal\log_monitor\Plugin\log_monitor\Scheduler;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\log_monitor\Scheduler\SchedulerPluginInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Base class for Scheduler plugin plugins.
 */
abstract class SchedulerConfigurablePluginBase extends SchedulerPluginBase implements PluginFormInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    if(empty($configuration['settings'])) {
      $configuration['settings'] = [];
    }
    $configuration['settings'] += $this->defaultSettings();
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  // Add common methods and abstract methods for your plugin type here.
  /**
   * {@inheritdoc}
   */
  public function defaultSettings() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return ['plugin_id' => $this->getPluginId()] + $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if(empty($configuration['settings'])) {
      $configuration['settings'] = [];
    }
    $configuration['settings'] += $this->defaultSettings();
    $this->configuration = $configuration;
  }


  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['settings'] = $form_state->getValues();
  }

}
