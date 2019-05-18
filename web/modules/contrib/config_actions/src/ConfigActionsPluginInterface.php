<?php

namespace Drupal\config_actions;

/**
 * Defines an interface for config actions plugins
 */
interface ConfigActionsPluginInterface {

  /**
   * Return a transformed version of the source config tree.
   *
   * @param array $source
   * @return array
   */
  public function transform(array $source);

  /**
   * Execute the action for this plugin.
   *
   * @param array $action: a config_actions action
   * @return mixed
   *   FALSE if there was a problem executing the plugin action
   */
  public function execute(array $action);

}
