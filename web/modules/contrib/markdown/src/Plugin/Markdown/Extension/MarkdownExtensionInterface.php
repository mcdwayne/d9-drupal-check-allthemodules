<?php

namespace Drupal\markdown\Plugin\Markdown\Extension;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\markdown\Plugin\Filter\MarkdownFilterInterface;

/**
 * Interface ExtensionInterface.
 */
interface MarkdownExtensionInterface extends ConfigurablePluginInterface {

  /**
   * Retrieves the default settings.
   *
   * @return array
   *   The default settings.
   */
  public function defaultSettings();

  /**
   * Retrieves a setting.
   *
   * @param string $name
   *   The name of the setting to retrieve.
   *
   * @return mixed
   *   The settings value or NULL if not set.
   */
  public function getSetting($name);

  /**
   * Retrieves the current settings.
   *
   * @return array
   *   The settings array
   */
  public function getSettings();

  /**
   * Indicates whether the extension is being used.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function isEnabled();

  /**
   * Returns the human readable label of the plugin.
   *
   * @return string
   *   The label.
   */
  public function label();

  /**
   * Sets a specific setting.
   *
   * @param string $name
   *   The name of the setting to set.
   * @param mixed $value
   *   (optional) The value to set. If not provided it will be removed.
   */
  public function setSetting($name, $value = NULL);

  /**
   * Provides settings to an extension.
   *
   * @param array $settings
   *   The settings array.
   */
  public function setSettings(array $settings = []);

  /**
   * Returns the configuration form elements specific to this plugin.
   *
   * @param array $form
   *   The form definition array for the block configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   * @param \Drupal\markdown\Plugin\Filter\MarkdownFilterInterface $filter
   *   The filter this form belongs to.
   *
   * @return array
   *   The renderable form array representing the entire configuration form.
   */
  public function settingsForm(array $form, FormStateInterface $form_state, MarkdownFilterInterface $filter);

}
