<?php

namespace Drupal\healthcheck\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\healthcheck\Finding\FindingStatus;
use Drupal\healthcheck\FindingServiceInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Healthcheck plugin plugins.
 */
abstract class HealthcheckPluginBase extends PluginBase implements HealthcheckPluginInterface, ContainerFactoryPluginInterface {

  /**
   * The finding service.
   *
   * @var \Drupal\healthcheck\FindingServiceInterface
   */
  protected $finding_service;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, FindingServiceInterface $finding_service) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->finding_service = $finding_service;

    $this->configuration += $this->defaultConfiguration();
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('healthcheck.finding')
    );
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
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $stop = 'me';
    foreach (array_keys($this->defaultConfiguration()) as $key) {
      $this->configuration[$key] = $form_state->getValue($key);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // Override this.
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
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
    $this->configuration = NestedArray::mergeDeep($this->defaultConfiguration(), $configuration);
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    $def = $this->getPluginDefinition();

    return !empty($def['label']) ? $def['label'] : '';
  }

  /**
   * {@inheritdoc}
   */
  public function getTags() {
    $def = $this->getPluginDefinition();

    return !empty($def['tags']) ? $def['tags'] : [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    $def = $this->getPluginDefinition();

    return !empty($def['description']) ? $def['description'] : '';
  }

  /**
   * Builds a new finding with the given status, key, and data.
   *
   * @param $status
   *   A status constant from FindingStatus.
   * @param $key
   *   The unique key of the finding.
   * @param array $data
   *   An array of key-value data, used for replacement. Optional.
   *
   * @return \Drupal\healthcheck\Finding\FindingInterface
   *   A new critical finding.
   *
   * @see FindingStatus
   */
  public function found($status, $key, $data = []) {
    return $this->finding_service->build($this, $key, $status, $data);
  }

  /**
   * Builds a new critical finding with the given key and data.
   *
   * @param $key
   *   The unique key of the finding.
   * @param array $data
   *   An array of key-value data, used for replacement. Optional.
   *
   * @return \Drupal\healthcheck\Finding\FindingInterface
   *   A new critical finding.
   */
  public function critical($key, $data = []) {
    return $this->found(FindingStatus::CRITICAL, $key, $data);
  }

  /**
   * Builds a new action requested finding with the given key and data.
   *
   * @param $key
   *   The unique key of the finding.
   * @param array $data
   *   An array of key-value data, used for replacement. Optional.
   *
   * @return \Drupal\healthcheck\Finding\FindingInterface
   *   A new action requested finding.
   */
  public function actionRequested($key, $data = []) {
    return $this->found(FindingStatus::ACTION_REQUESTED, $key, $data);
  }

  /**
   * Builds a new needs review finding with the given key and data.
   *
   * @param $key
   *   The unique key of the finding.
   * @param array $data
   *   An array of key-value data, used for replacement. Optional.
   *
   * @return \Drupal\healthcheck\Finding\FindingInterface
   *   A new needs review finding.
   */
  public function needsReview($key, $data = []) {
    return $this->found(FindingStatus::NEEDS_REVIEW, $key, $data);
  }

  /**
   * Builds a new no action requested finding with the given key and data.
   *
   * @param $key
   *   The unique key of the finding.
   * @param array $data
   *   An array of key-value data, used for replacement. Optional.
   *
   * @return \Drupal\healthcheck\Finding\FindingInterface
   *   A new no action requested finding.
   */
  public function noActionRequired($key, $data = []) {
    return $this->found(FindingStatus::NO_ACTION_REQUIRED, $key, $data);
  }

  /**
   * Builds a new not performed finding with the given key and data.
   *
   * @param $key
   *   The unique key of the finding.
   * @param array $data
   *   An array of key-value data, used for replacement. Optional.
   *
   * @return \Drupal\healthcheck\Finding\FindingInterface
   *   A new not performed finding.
   */
  public function notPerformed($key, $data = []) {
    return $this->found(FindingStatus::NOT_PERFORMED, $key, $data);
  }

}
