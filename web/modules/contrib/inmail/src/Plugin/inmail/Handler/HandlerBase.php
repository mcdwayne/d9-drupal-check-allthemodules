<?php

namespace Drupal\inmail\Plugin\inmail\Handler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\inmail\InmailPluginBase;

/**
 * Base class for message handler plugins.
 *
 * This provides dumb implementations for most handler methods, but leaves
 * ::invoke() and ::help() abstract.
 *
 * @ingroup handler
 */
abstract class HandlerBase extends InmailPluginBase implements HandlerInterface {

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
    return array();
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
   * that HandlerForm can use it to update and save the config entity.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
  }

}
