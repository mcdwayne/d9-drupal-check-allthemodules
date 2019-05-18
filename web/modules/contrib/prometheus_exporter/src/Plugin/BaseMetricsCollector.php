<?php

namespace Drupal\prometheus_exporter\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;

/**
 * Base class for metrics collector plugins.
 */
abstract class BaseMetricsCollector extends PluginBase implements MetricsCollectorInterface {

  /**
   * The name of the provider that owns this collector.
   *
   * @var string
   */
  protected $provider;

  /**
   * A Boolean indicating whether this collector is enabled.
   *
   * @var bool
   */
  protected $enabled = FALSE;

  /**
   * The weight of this collector compared to others in a collector collection.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * An associative array containing the configured settings of this collector.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'provider' => $this->pluginDefinition['provider'],
      'enabled' => $this->enabled,
      'weight' => $this->weight,
      'settings' => $this->settings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (isset($configuration['enabled'])) {
      $this->enabled = (bool) $configuration['enabled'];
    }
    if (isset($configuration['weight'])) {
      $this->weight = (int) $configuration['weight'];
    }
    if (isset($configuration['settings'])) {
      $this->settings = (array) $configuration['settings'];
    }

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'id' => $this->getPluginId(),
      'provider' => $this->pluginDefinition['provider'],
      'enabled' => FALSE,
      'weight' => $this->pluginDefinition['weight'] ?: 0,
      'settings' => $this->pluginDefinition['settings'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['title'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * {@inheritdoc}
   */
  public function isEnabled() {
    return $this->enabled;
  }

  /**
   * {@inheritdoc}
   */
  public function applies() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * {@inheritdoc}
   */
  public function getProvider() {
    return $this->provider;
  }

  /**
   * Gets the metric namespace.
   *
   * @return string
   *   The namespace.
   */
  protected function getNamespace() {
    return "drupal_" . $this->getPluginId();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
  }

}
