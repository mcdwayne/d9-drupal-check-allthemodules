<?php

namespace Drupal\config_overridden\Plugin\ConfigFormOverrider;

use Drupal\config_overridden\Plugin\ConfigFormOverriderBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class FormOverriderDefault.
 *
 * @ConfigFormOverrider(
 *   id = "form_null",
 *   name = @Translation("Dummy processor"),
 *   weight = 0
 * )
 */
class ConfigFormOverriderNull extends ConfigFormOverriderBase {
  /**
   * Overrides highlighted form.
   */
  public function highlightOverrides() {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function isApplicable() {
    return FALSE;
  }
}
