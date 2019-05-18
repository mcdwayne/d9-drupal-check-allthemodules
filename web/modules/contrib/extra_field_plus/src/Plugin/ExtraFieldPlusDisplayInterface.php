<?php

namespace Drupal\extra_field_plus\Plugin;

use Drupal\extra_field\Plugin\ExtraFieldDisplayInterface;

/**
 * Defines an interface for Extra Field Plus Display plugins.
 */
interface ExtraFieldPlusDisplayInterface extends ExtraFieldDisplayInterface {

  /**
   * Returns field settings.
   *
   * @return array
   *   The field settings.
   */
  public function getSettings();

  /**
   * Returns field setting.
   *
   * @param string $name
   *   Setting name.
   *
   * @return mixed|null
   *   The field setting or NULL if does not exist.
   */
  public function getSetting($name);

  /**
   * Returns field settings form.
   *
   * @return array
   *   The field settings form.
   *   Example: [key_1 => [...], key_2 => [...], ...].
   */
  public function getSettingsForm();

  /**
   * Returns field settings form default values.
   *
   * @return array
   *   The form values.
   *   Example: [key_1 => value_1, key_2 => value_2,...].
   */
  public function getDefaultFormValues();

}
