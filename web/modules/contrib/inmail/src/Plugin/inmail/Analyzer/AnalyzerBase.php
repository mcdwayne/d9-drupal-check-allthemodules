<?php

namespace Drupal\inmail\Plugin\inmail\Analyzer;

use Drupal\Core\Form\FormStateInterface;
use Drupal\inmail\InmailPluginBase;

/**
 * Base class for message analyzer plugins.
 *
 * @ingroup analyzer
 */
abstract class AnalyzerBase extends InmailPluginBase implements AnalyzerInterface {

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
  public function defaultConfiguration() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    // @todo Implement HandlerConfig::calculateDependencies() https://www.drupal.org/node/2379929
    return array();
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
  }

  /**
   * Form submission handler.
   *
   * Override this method to update $this->configuration with form input, so
   * that AnalyzerForm can use it to update and save the config entity.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
