<?php

namespace Drupal\log_monitor\Plugin\log_monitor\Reaction;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;
use Drupal\log_monitor\Reaction\ReactionPluginInterface;

/**
 * Base class for Reaction plugin plugins.
 */
abstract class ReactionPluginBase extends PluginBase implements ReactionPluginInterface, PluginFormInterface {


  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, array $plugin_definition) {
    if(!isset($configuration['settings'])) {
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
  public function id() {
    return isset($this->configuration['id']) ? $this->configuration['id'] : NULL;
  }

  public function setId($id) {
    $configuration = $this->getConfiguration();
    $configuration['id'] = $id;
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return ['id' => $this->id(), 'plugin_id' => $this->getPluginId()] + $this->configuration;
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
