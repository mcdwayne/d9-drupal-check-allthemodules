<?php

namespace Drupal\inmail\Plugin\inmail\Deliverer;
use Drupal\Core\Form\FormStateInterface;
use Drupal\inmail\InmailPluginBase;

/**
 * Base class for mail deliverers.
 *
 * This class should be extended by passive deliverers. Deliverers that can be
 * executed should extend \Drupal\inmail\Plugin\inmail\Deliverer\FetcherBase
 * instead.
 *
 * @ingroup deliverer
 */
abstract class DelivererBase extends InmailPluginBase implements DelivererInterface {

  /**
   * {@inheritdoc}
   */
  public function getLabel() {
    return $this->pluginDefinition['label'];
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
    return $this;
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
  public function defaultConfiguration() {
    return [];
  }

  /**
   * Returns a state key appropriate for the given state property.
   *
   * @param string $key
   *   Name of key.
   *
   * @return string
   *   An appropriate name for a state property of the deliverer config
   *   associated with this fetcher.
   */
  protected function makeStateKey($key) {
    $config_id = $this->getConfiguration()['config_id'];
    return 'inmail.deliverer.' . $config_id . '.' . $key;
  }

  /**
   * {@inheritdoc}
   */
  public function getProcessedCount() {
    return \Drupal::state()->get($this->makeStateKey('processed_count'));
  }

  /**
   * {@inheritdoc}
   */
  public function setProcessedCount($count) {
    \Drupal::state()->set($this->makeStateKey('processed_count'), $count);
  }

  /**
   * {@inheritdoc}
   */
  public function success($key) {
    $this->setProcessedCount($this->getProcessedCount() + 1);
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    // No form by default.
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // No validation by default.
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Entity\EntityForm $form_object */
    $form_object = $form_state->getFormObject();
    $this->configuration['config_id'] = $form_object->getEntity()->id();
  }
}
