<?php

namespace Drupal\config_actions;

/**
 * Trait for adding options to perform path validation for a plugin.
 *
 * Can ONLY be used in a plugin that extends the ConfigActionsPluginBase class.
 */
trait ConfigActionsValidateTrait {

  /**
   * Path to config being altered
   * @var array
   */
  protected $path;

  /**
   * Optional path to the $current_value to be used instead of $path.
   * @var array
   */
  protected $value_path;

  /**
   * Current data at path to be validated.
   * @var mixed
   */
  protected $current_value;

  /**
   * Perform validation of the path.
   * Call this from the plugin transform or execute method.
   * Throws exception if validation fails.
   * @param array $source
   */
  protected function validatePath(array $source) {
    if (!is_null($this->current_value)) {
      $path = (!empty($this->value_path)) ? $this->value_path : $this->path;
      $actual_value = ConfigActionsTransform::read($source, $path);
      if ($actual_value !== $this->current_value) {
        throw new \Exception('Failed to validate path value for config action.');
      }
    }
  }

}
