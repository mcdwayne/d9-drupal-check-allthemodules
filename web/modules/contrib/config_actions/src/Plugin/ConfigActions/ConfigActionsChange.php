<?php

namespace Drupal\config_actions\Plugin\ConfigActions;

use Drupal\config_actions\ConfigActionsPluginBase;
use Drupal\config_actions\ConfigActionsTransform;
use Drupal\config_actions\ConfigActionsValidateTrait;

/**
 * Plugin for changing data.
 *
 * @ConfigActionsPlugin(
 *   id = "change",
 *   description = @Translation("Change data."),
 *   options = {
 *     "path" = { },
 *     "value" = NULL,
 *     "value_path" = { },
 *     "current_value" = NULL,
 *   },
 *   replace_in = { "path", "value", "value_path", "current_value", "dest", "load" },
 * )
 */
class ConfigActionsChange extends ConfigActionsPluginBase {
  use ConfigActionsValidateTrait;

  /**
   * Config data to be added.
   * @var mixed
   */
  protected $value;

  /**
   * Main transform to perform the change
   */
  public function transform(array $source) {
    if (isset($this->value)) {
      $this->validatePath($source);
      $source = ConfigActionsTransform::change($source, $this->path, $this->value);
    }
    return $source;
  }

}
