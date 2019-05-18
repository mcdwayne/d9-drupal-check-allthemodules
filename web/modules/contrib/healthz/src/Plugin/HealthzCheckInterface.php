<?php

namespace Drupal\healthz\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Defines the interface for healthz check plugins.
 */
interface HealthzCheckInterface extends ConfigurablePluginInterface, PluginInspectionInterface {

  /**
   * Returns the administrative label for this plugin.
   *
   * @return string
   *   The admin label.
   */
  public function getLabel();

  /**
   * Returns the administrative description for this plugin.
   *
   * @return string
   *   The admin description.
   */
  public function getDescription();

  /**
   * Returns whether the plugin is enabled.
   *
   * @return bool
   *   Whether the plugin is enabled.
   */
  public function getStatus();

  /**
   * Returns the plugin weight.
   *
   * @return int
   *   The plugin weight.
   */
  public function getWeight();

  /**
   * Returns the plugin's provider.
   *
   * @return string
   *   The plugin's provider.
   */
  public function getProvider();

  /**
   * Returns the status code to return on failure.
   *
   * @return int
   *   The status code.
   */
  public function getFailureStatusCode();

  /**
   * Generates a check's settings form.
   *
   * @param array $form
   *   A minimally prepopulated form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The state of the (entire) configuration form.
   *
   * @return array
   *   The $form array with additional form elements for the settings of this
   *   check. The submitted form values should match $this->settings.
   */
  public function settingsForm(array $form, FormStateInterface $form_state);

  /**
   * Returns a boolean based on whether this healthz check applies.
   *
   * @return bool
   *   Whether the check applies.
   */
  public function applies();

  /**
   * Performs the Healthz check.
   *
   * @return bool
   *   Whether the check was successful.
   */
  public function check();

  /**
   * Returns a list of errors for when the check fails.
   *
   * @return array
   *   A list of errors to print.
   */
  public function getErrors();

}
