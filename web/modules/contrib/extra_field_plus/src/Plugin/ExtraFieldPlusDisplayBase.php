<?php

namespace Drupal\extra_field_plus\Plugin;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\extra_field\Plugin\ExtraFieldDisplayBase;

/**
 * Base class for Extra field Plus Display plugins.
 */
abstract class ExtraFieldPlusDisplayBase extends ExtraFieldDisplayBase implements ExtraFieldPlusDisplayInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function getSettings() {
    $field_id = 'extra_field_' . $this->getPluginId();
    $display = $this->getEntityViewDisplay();
    $component = $display->getComponent($field_id);

    $default_settings = (array) $this->getDefaultFormValues();
    if (!empty($component['settings'])) {
      $settings = array_merge($default_settings, array_intersect_key($component['settings'], $default_settings));
    }
    else {
      $settings = $default_settings;
    }

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function getSetting($name) {
    $settings = $this->getSettings();

    return isset($settings[$name]) ? $settings[$name] : NULL;
  }

  /**
   * Provides field settings form.
   *
   * @return array
   *   The field settings form.
   */
  protected function settingsForm() {
    $elements = [];

    return $elements;
  }

  /**
   * {@inheritdoc}
   */
  public function getSettingsForm() {
    $default_values = (array) $this->getDefaultFormValues();
    $elements = (array) $this->settingsForm();

    if (!empty($elements)) {
      foreach ($elements as $name => &$element) {
        $element['#default_value'] = isset($default_values[$name]) ? $default_values[$name] : '';
      }
    }

    return $elements;
  }

  /**
   * Provides field settings form default values.
   *
   * @return array
   *   The form values.
   */
  protected function defaultFormValues() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultFormValues() {
    return $this->defaultFormValues();
  }

}
