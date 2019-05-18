<?php

namespace Drupal\healthz\Plugin;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Provides a base class for HealthzCheck plugins.
 */
class HealthzCheckBase extends PluginBase implements HealthzCheckInterface {

  /**
   * The name of the provider that owns this check.
   *
   * @var string
   */
  protected $provider;

  /**
   * A Boolean indicating whether this check is enabled.
   *
   * @var bool
   */
  protected $status = FALSE;

  /**
   * The weight of this check compared to others in a check collection.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * The status code to return on failure.
   *
   * @var int
   */
  public $failureStatusCode = 500;

  /**
   * An associative array containing the configured settings of this check.
   *
   * @var array
   */
  protected $settings = [];

  /**
   * An array of errors set by the check().
   *
   * @var array
   *   The array of errors.
   */
  protected $errors = [];

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
      'status' => $this->status,
      'weight' => $this->weight,
      'failure_status_code' => $this->failureStatusCode,
      'settings' => $this->settings,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    if (isset($configuration['status'])) {
      $this->status = (bool) $configuration['status'];
    }
    if (isset($configuration['weight'])) {
      $this->weight = (int) $configuration['weight'];
    }
    if (isset($configuration['failure_status_code'])) {
      $this->failureStatusCode = (int) $configuration['failure_status_code'];
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
      'status' => FALSE,
      'weight' => $this->pluginDefinition['weight'] ?: 0,
      'settings' => $this->pluginDefinition['settings'],
      'failure_status_code' => $this->pluginDefinition['failureStatusCode'],
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
  public function getFailureStatusCode() {
    return $this->failureStatusCode;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->status;
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
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [];
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
  public function check() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function getErrors() {
    return $this->errors;
  }

  /**
   * Add an error for this check.
   *
   * @param \Drupal\Core\StringTranslation\TranslatableMarkup $message
   *   The translated error message.
   */
  public function addError(TranslatableMarkup $message) {
    $this->errors[] = $message;
  }

}
