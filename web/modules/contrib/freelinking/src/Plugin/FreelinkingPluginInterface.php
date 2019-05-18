<?php

namespace Drupal\freelinking\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginInspectionInterface;
use Drupal\Core\Form\FormStateInterface;

/**
 * Describes the Freelinking plugin.
 */
interface FreelinkingPluginInterface extends ConfigurablePluginInterface, PluginInspectionInterface {

  /**
   * Provides tips for this freelinking plugin.
   *
   * Tips are displayed as part of the freelinking filter plugin.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Translatable markup.
   */
  public function getTip();

  /**
   * A regular expression string to indicate what to replace for this plugin.
   *
   * @return string
   *   A regular expression string.
   */
  public function getIndicator();

  /**
   * Plugin configuration form.
   *
   * @param array $form
   *   The form element array for the filter plugin.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state of the parent form.
   *
   * @return array
   *   The configuration form to attach to Freelinking settings form.
   */
  public function settingsForm(array $form, FormStateInterface $form_state);

  /**
   * Build a link with the plugin.
   *
   * @param array $target
   *   The target array with including the following keys:
   *   - text: The text to display in the URL.
   *   - indicator: The indicator string.
   *   - dest: The destination string for the plugin to turn into a URI.
   *   - tooltip: An optional tooltip.
   *   - language: A language object.
   *
   * @return array
   *   Link array.
   */
  public function buildLink(array $target);

  /**
   * Determine if the plugin is built-in (always on).
   *
   * @return bool
   *   TRUE if the plugin is hidden from filter configuration.
   */
  public function isHidden();

  /**
   * Get the failover plugin ID (if applicable).
   *
   * @return string
   *   The plugin ID of the failover plugin or an empty string if no plugin
   *   available.
   */
  public function getFailoverPluginId();

}
