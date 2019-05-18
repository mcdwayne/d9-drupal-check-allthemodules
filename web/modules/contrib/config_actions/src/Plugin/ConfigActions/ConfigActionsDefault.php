<?php

namespace Drupal\config_actions\Plugin\ConfigActions;

use Drupal\config_actions\ConfigActionsPluginBase;
use Drupal\config_actions\ConfigActionsTransform;
use Drupal\config_actions\ConfigActionsValidateTrait;

/**
 * Default Plugin for changing, adding, deleting data.
 *
 * @ConfigActionsPlugin(
 *   id = "default",
 *   description = @Translation("Change, Add, Delete data."),
 *   options = {
 *     "path" = { },
 *     "value" = NULL,
 *     "change" = NULL,
 *     "add" = NULL,
 *     "delete" = NULL,
 *     "value_path" = { },
 *     "current_value" = NULL,
 *   },
 *   replace_in = { "path", "value", "change", "add", "delete", "value_path", "current_value", "dest", "load" },
 * )
 */
class ConfigActionsDefault extends ConfigActionsPluginBase {
  use ConfigActionsValidateTrait;

  /**
   * Config data to be changed.
   * Deprecated in favor of $changed.
   *
   * @var mixed
   */
  protected $value;

  /**
   * Config data to be changed.
   * @var mixed
   */
  protected $change;

  /**
   * Config data to be added.
   * @var mixed
   */
  protected $add;

  /**
   * Config data to be deleted.
   * @var mixed
   */
  protected $delete;

  /**
   * Main transform to perform the change
   */
  public function transform(array $source) {
    $this->validatePath($source);
    if (isset($this->value)) {
      $source = ConfigActionsTransform::change($source, $this->path, $this->value);
    }
    if (isset($this->change)) {
      $source = ConfigActionsTransform::change($source, $this->path, $this->change);
    }
    if (isset($this->add)) {
      $source = ConfigActionsTransform::add($source, $this->path, $this->add, TRUE);
    }
    if (isset($this->delete)) {
      $source = ConfigActionsTransform::delete($source, $this->path, $this->delete, TRUE);
    }
    return $source;
  }

}
