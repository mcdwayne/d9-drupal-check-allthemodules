<?php

/**
 * @file
 * Rules declarations.
 */

/**
 * Implements hook_rules_action_info().
 */
function drush_task_rules_action_info() {
  return array(
    'drush_task_rules_action_run_drush_task' => array(
      'label' => t('Run Drush Task'),
      'group' => t('Service'),
      'parameter' => array(

        'command' => array(
          'type' => 'text',
          'restriction' => 'input',
          'label' => t('Command'),
          'description' => t('Drush command.'),
          'optional' => FALSE,
          'default value' => 'status',
          'allow null' => FALSE,
          'sanitize' => TRUE,
        ),

        'arguments' => array(
          'type' => 'text',
          'restriction' => 'input',
          'label' => t('Arguments'),
          'description' => t('Enter commandline options and arguments, space separated. Tokens are supported.'),
          'optional' => TRUE,
          'default value' => '',
          'allow null' => TRUE,
          'sanitize' => TRUE,
        ),

        'site_alias' => array(
          'type' => array('text', 'token'),
          'default mode' => 'input',
          'label' => t('Site Alias'),
          'description' => t('OPTIONAL. The Site Alias. If not set, will use the current site as default context.'),
          'optional' => TRUE,
          'default value' => '',
          'allow null' => TRUE,
        ),
      ),
      'provides' => array(
        'drushtask' => array(
          'label' => 'Task',
          'type' => 'drushtask',
        ),
      ),
    ),
  );
}

/**
 * Sends a glip message with the given values.
 *
 * An action callback.
 *
 * @param string $command
 *   Drush command.
 * @param string $options
 *   Drush options.
 * @param string $argument
 *   Drush arguments.
 * @param string $site_alias
 *   Optional drush site-alias or site identifier.
 * @param array $params
 *   Array of currently avaiable parameters.
 * @param RulesState $state
 *   Something about the action.
 * @param RulesAction $action
 *   Currently running rule context.
 * @param string $op
 *   Probably 'execute'.
 *
 * @return DrushTask
 *   A rules_action_execution_callback() .
 *
 * @see rules_action_execution_callback()
 */
function drush_task_rules_action_run_drush_task($command, $arguments, $site_alias, $params, RulesState $state, RulesAction $action, $op) {
  $task = new DrushTask($command, $arguments, $site_alias);

  // IF a site_alias is set, we should assume there is a chance of a timeout or
  // other long-running task.
  // Returning a response immediately is only possible when not using the queue.
  $task->run();
  return array('drushtask' => $task);

  $response = array(
    'success' => empty($task->resultCode),
    'result_code' => $task->resultCode,
    'result_text' => $task->resultText,
    'result' => $task->result,
  );
  return array('drushtask' => $response);

  // drush_task_enqueue($task);
}

/**
 * The result of running a drush_task is a DrushTask object.
 *
 * ...which can be inspected for results.
 * Here we list the attributes of the task object
 * that we wany Rules to be able to access.
 * They show up in the UI for inspection or mapping.
 *
 * They should be provided in the response from the action.
 *
 * Implements hook_rules_data_info().
 *
 * @inheritdoc
 * @see hook_rules_data_info()
 */
function drush_task_rules_data_info() {
  return array(
    'drushtask' => array(
      'label'  => t('Drush Task'),
      'group'  => t('Service'),
      'wrap' => TRUE,
      'property info' => array(
        'success' => array(
          'label' => t('success'),
          'type' => 'boolean',
          'getter callback' => 'drush_tasks_drushtask_get_success',
        ),
        'result_code' => array(
          'label' => t('Result code'),
          'type' => 'text',
          'getter callback' => 'drush_tasks_drushtask_get_result_code',
        ),
        'result' => array(
          'label' => t('Result data'),
          // As I can't reliably say what format this comes in, I can't
          // reliably map it to any other type as a value.
          // Can I get a universal donor?
          'type' => 'unknown',
          'getter callback' => 'drush_tasks_drushtask_get_result',
        ),
        'result_text' => array(
          'label' => t('Result text'),
          'type' => 'text',
          'getter callback' => 'drush_tasks_drushtask_get_result_text',
        ),
      ),

    ),
  );
}

/**
 * Getter callbacks for the values.
 *
 * Neccessary as Feeds prohibits camelCase,
 * but OO standards want it. This I can't use native getters.
 *
 * These are functiond not methods, as the $task isn't a fully-formed
 * entity, so it doesn't have entity_metadata_wappers etc helping it.
 */
function drush_tasks_drushtask_get_success($task) {
  return $task->success;
}
function drush_tasks_drushtask_get_result_code($task) {
  return $task->resultCode;
}
function drush_tasks_drushtask_get_result($task) {
  return $task->result;
}
function drush_tasks_drushtask_get_result_text($task) {
  return $task->resultText;
}
