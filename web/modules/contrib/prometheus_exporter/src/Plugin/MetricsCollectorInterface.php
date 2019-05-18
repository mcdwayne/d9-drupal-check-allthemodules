<?php

namespace Drupal\prometheus_exporter\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Collects metrics for export to prometheus.
 */
interface MetricsCollectorInterface extends ConfigurablePluginInterface, PluginInspectionInterface {

  /**
   * Collects metrics to be exported.
   *
   * @return \PNX\Prometheus\Metric[]
   *   The metrics to be exported.
   */
  public function collectMetrics();

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
  public function isEnabled();

  /**
   * Returns whether the plugin applies.
   *
   * This can be used by plugins to avoid running if their dependencies are
   * not satisfied.
   *
   * @return bool
   *   Whether the plugin applies.
   */
  public function applies();

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

}
