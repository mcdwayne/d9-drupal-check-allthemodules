<?php

namespace Drupal\config_actions\Commands;

use Drush\Commands\DrushCommands;
use Drupal\config_actions\ConfigActionsServiceInterface;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 *
 * See these files for an example of injecting Drupal services:
 *   - http://cgit.drupalcode.org/devel/tree/src/Commands/DevelCommands.php
 *   - http://cgit.drupalcode.org/devel/tree/drush.services.yml
 */
class ConfigActionsCommands extends DrushCommands {

  /**
   * The config_actions service.
   *
   * @var \Drupal\config_actions\ConfigActionsServiceInterface
   */
  protected $configActions;

  /**
   * ConfigActionsCommands constructor.
   *
   * @param \Drupal\config_actions\ConfigActionsServiceInterface $config_actions
   *   The config_actions service.
   */
  public function __construct(ConfigActionsServiceInterface $config_actions) {
    parent::__construct();
    $this->configActions = $config_actions;
  }

  /**
   * Display a list of available actions in modules.
   *
   * @param $module_name
   *   Optional name of module to filter the list.
   * @param $file
   *   Optional name of file within module to list.
   *
   * @command config:actions-list
   * @aliases cal,config-actions-list
   */
  public function actionsList($module_name = '', $file = '') {
    $list = $this->configActions->listAll($module_name, $file);
    if (empty($list)) {
      $this->output()->writeln(dt('No actions found.'));
    }
    else {
      foreach ($list as $module => $files) {
        $this->output()->writeln(dt('Module: @name', array('@name' => $module)));
        foreach ($files as $filename => $actions) {
          $this->output()->writeln(dt('  File: @file', array('@file' => $filename)));
          foreach ($actions as $action_id => $action) {
            if (!empty($action_id)) {
              $this->output()->writeln(dt('    @action', array('@action' => $action_id)));
            }
          }
        }
      }
    }
  }

  /**
   * Execute actions within a module.
   *
   * @param $module_name
   *   Name of module containing the action.  If omitted, all actions in all modules are executed.
   * @param $file
   *   Optional name of file within module containing action. If omitted, all actions in the module are executed.
   * @param $action_id
   *   Optional name of action to execute.  If omitted, all actions in the file are executed.
   *
   * @command config:actions-run
   * @aliases car,config-actions-run
   */
  public function actionsRun($module_name = '', $file = '', $action_id = '') {
    $result = $this->configActions->importAction($module_name, $action_id, $file);
    if (empty($result)) {
      $this->output()->writeln(dt('No actions were executed.'));
    }
    else {
      foreach ($result as $source => $config) {
        if (is_null($config)) {
          $this->output()->writeln(dt('  Action: @action - SKIPPED', array('@action' => $source)));
        }
        else {
          $this->output()->writeln(dt('  Action: @action', array('@action' => $source)));
        }
      }
    }
  }

}
