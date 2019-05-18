<?php

namespace Drupal\config_actions;

/**
 * Defines an interface for config actions service
 */
interface ConfigActionsServiceInterface {

  /**
   *  The sub-directory of a module containing config action files.
   */
  const CONFIG_ACTIONS_CONFIG_DIR = 'config/actions';

  /**
   * Process a single config_actions action
   *
   * @param array $action
   *   array of action data
   * @param array $options
   *   optional array of action options
   *   If $options is empty, values are taken from $action data
   * @param string $action_id
   *   The id string of the action to be executed.  If omitted, execute all
   *   actions in the $action array. Nested actions can be separated with a
   *   colon, such as "action:subaction"
   * @return mixed
   *   Returns the data processed, or NULL if no action was processed.
   */
  function processAction(array $action, array $options = [], $action_id = '');

  /**
   * Return a list of actions within an actions array
   *
   * @param array $actions
   *   array of action data
   * @return array
   *   Returns the list of action ids contained within the action data
   *   Nested actions have the form parent:child
   */
  function listActions(array $actions);

  /**
   * Process a specific action id from a given module
   * @param string $module_name
   * @param string $action_id
   *   if empty, process all actions in the module. Nested actions can be
   *   separated with a colon, such as "action:subaction"
   * @param string $file
   *   if empty, process all actions files in the module. Otherwise only
   *   process actions in the named file.  Just the file, not the path.
   *   The .yml extension is optional, but you cannot reference non *.yml files.
   * @param array $variables
   *   list of action variables to override imported behavior.
   * @return mixed
   *   Returns data imported or NULL if nothing was found.
   *   Data is keyed by the name of the action file that was found.
   */
  public function importAction($module_name, $action_id = '', $file = '', $variables = []);

  /**
   * Load data from a given source plugin.
   * @param mixed $source specifier
   * @param string $type
   *   source plugin id
   * @param string $base
   *   optional base path
   */
  public function loadSource($source, $type = '', $base = '');

  /**
   * Save data from a given source plugin.
   * @param mixed $data to be saved
   * @param mixed $dest specifier
   * @param string $type
   *   source plugin id
   * @param string $base
   *   optional base path
   * @param bool $force
   *   True if data should be saved regardless of pipeline
   * @return bool
   *   True if plugin could save data
   */
  public function saveSource($data, $dest, $type = '', $base = '', $force = FALSE);

  /**
   * Force saving all cached sources.
   */
  public function saveAll();

  /**
   * Clear the internal cache of source plugins.
   * Saves any changed data first via SaveAll.
   */
  public function clearSourceCache();

  /**
   * Return a list of actions within a module
   * @param string $module_name
   *   If omitted, all actions are listed
   * @param string $file
   *   if empty, list all actions files in the module. Otherwise only
   *   list actions in the named file.  Just the file, not the path.
   *   Do not include the .yml extension.
   * @return array keyed by module name and action file name
   *   each element an action array keyed by action id
   *   $result[$module_name][$file_name][$action_id] = action_data
   */
  public function listAll($module_name = '', $file = '');

  /**
   * Get or set the autoExecute property of the service.  When autoExecute
   * is TRUE, actions marked with the "auto: false" option are skipped.
   * If FALSE, all actions can are executed.
   * @param null|bool $value
   *   If specified, set the value of the autoExecute property
   * @return bool returns the value of the autoExecute property
   */
  public function autoExecute($value = NULL);

}
