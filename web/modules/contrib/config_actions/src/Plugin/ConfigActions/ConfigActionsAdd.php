<?php

namespace Drupal\config_actions\Plugin\ConfigActions;

use Drupal\config_actions\ConfigActionsPluginBase;
use Drupal\config_actions\ConfigActionsTransform;
use Drupal\config_actions\ConfigActionsValidateTrait;

/**
 * Plugin for changing data.
 *
 * @ConfigActionsPlugin(
 *   id = "add",
 *   description = @Translation("Add data."),
 *   options = {
 *     "path" = { },
 *     "value" = "",
 *     "value_path" = { },
 *     "current_value" = NULL,
 *   },
 *   replace_in = { "path", "value", "value_path", "current_value", "dest" },
 * )
 */
class ConfigActionsAdd extends ConfigActionsPluginBase {
  use ConfigActionsValidateTrait;

  /**
   * Config data to be added.
   * @var mixed
   */
  protected $value;

  /**
   * Main transform to perform the add
   */
  public function transform(array $source) {
    $this->validatePath($source);
    return ConfigActionsTransform::add($source, $this->path, $this->value, TRUE);
  }

}
