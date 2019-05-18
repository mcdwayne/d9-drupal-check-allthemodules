<?php

namespace Drupal\config_actions\Plugin\ConfigActions;

use Drupal\config_actions\ConfigActionsPluginBase;
use Drupal\Component\Utility\NestedArray;

/**
 * Plugin for including an action from another module.
 *
 * @ConfigActionsPlugin(
 *   id = "include",
 *   description = @Translation("Include action."),
 *   options = {
 *     "module" = "",
 *     "file" = "",
 *     "action" = "",
 *     "template" = "",
 *   }
 * )
 */
class ConfigActionsInclude extends ConfigActionsPluginBase {

  /**
   * Name of module containing action.
   * @var string
   */
  protected $module;

  /**
   * Optional Name of file to include.
   * @var string
   */
  protected $file;

  /**
   * Optional Name of action to include.
   * @var string
   */
  protected $action;

  /**
   * The template file to be loaded from the master config/templates list.
   * @var
   */
  protected $template;

  /**
   * Remove plugin-specific options to just leave those to override import.
   * @param $action
   */
  protected function cleanAction($action) {
    unset($action['plugin']);
    unset($action['module']);
    unset($action['file']);
    unset($action['action']);
    // Ensure included actions get executed.
    $action['auto'] = TRUE;
    return $action;
  }

  /**
   * Main execute to perform the include
   */
  public function execute(array $action) {
    if (isset($this->replace)) {
      $action['replace'] = NestedArray::mergeDeepArray([
        $this->replace,
        isset($action['replace']) ? $action['replace'] : []
      ], TRUE);
    }
    $action = $this->cleanAction($action);
    return $this->actionService->importAction($this->module, $this->action, $this->file, $action);
  }

}
