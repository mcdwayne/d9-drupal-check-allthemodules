<?php

namespace Drupal\config_actions\Plugin\ConfigActions;

use Drupal\config_actions\ConfigActionsPluginBase;
use Drupal\config_actions\ConfigActionsTransform;
use Drupal\config_actions\ConfigActionsValidateTrait;

/**
 * Plugin for deleting data.
 *
 * @ConfigActionsPlugin(
 *   id = "delete",
 *   description = @Translation("Delete data."),
 *   options = {
 *     "path" = { },
 *     "value_path" = { },
 *     "current_value" = NULL,
 *     "prune" = FALSE,
 *   },
 *   replace_in = { "path", "value_path", "current_value" },
 * )
 */
class ConfigActionsDelete extends ConfigActionsPluginBase {
  use ConfigActionsValidateTrait;

  /**
   * True if key should be unset (pruned) rather than just cleared.
   * @var bool
   */
  protected $prune;

  /**
   * Main transform to perform the deletion
   */
  public function transform(array $source) {
    $this->validatePath($source);
    $result = ConfigActionsTransform::delete($source, $this->path, $this->prune);
    // If entire result is pruned, return an empty array to cause config to
    // be deleted.
    return !empty($result) ? $result : [];
  }

}
