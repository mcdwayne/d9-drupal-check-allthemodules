<?php
/**
 * @file
 * Handle the 'drealty_listing' override task.
 *
 * This plugin overrides drealty_listing/%drealty_listing and reroutes it to the
 * page manager, where a list of tasks can be used to service this request based
 * upon criteria supplied by access plugins.
 */

/**
 * Specialized implementation of hook_page_manager_task_tasks().
 *
 * See api-task.html for more information.
 */
function drealty_drealty_listing_page_manager_tasks() {
  return array(
    // This is a 'page' task and will fall under the page admin UI.
    'task type' => 'page',
    'title' => t('Drealty listing'),
    'admin title' => t('Drealty listing'),
    'admin description' => t('When enabled, this overrides the default behavior for Drealty listings at <em>/drealty_listings/%drealty_listing</em>.'),
    'admin path' => 'drealty_listing/%drealty_listing',
    // Menu hooks so that we can alter the default entry.
    // module-name_task-name_menu_alter() convention.
    'hook menu alter' => 'drealty_drealty_listing_menu_alter',
    // This task provides the 'drealty_listing' context to the panel.
    'handler type' => 'context',
    'get arguments' => 'drealty_drealty_listing_get_arguments',
    'get context placeholders' => 'drealty_drealty_get_contexts',
    // Allow this panel-page to be enabled or disabled.
    'disabled' => variable_get('drealty_drealty_listing_disabled', TRUE),
    'enable callback' => 'drealty_drealty_listing_enable',
  );
};

/**
 * Menu alter callback.
 */
function drealty_drealty_listing_menu_alter(&$items, $task) {
  if (variable_get('drealty_drealty_listing_disabled', TRUE)) {
    // The panel is disabled, leave the default menu entry.
    return;
  }

  // Check whether the callbacks at drealty_listing/%drealty_listing match those
  // provided by the drealty module. If not, the URL is already overridden.
  $page_callback = $items['drealty_listing/%drealty_listing']['page callback'];
  $page_arguments = $items['drealty_listing/%drealty_listing']['page arguments'];
  $is_using_default = ($page_callback == 'drealty_listing_view' && $page_arguments == array(1));

  if ($is_using_default || variable_get('page_manager_override_anyway', FALSE)) {
    $items['drealty_listing/%drealty_listing']['page callback'] = 'drealty_drealty_listing_page';
    $items['drealty_listing/%drealty_listing']['file path'] = $task['path'];
    $items['drealty_listing/%drealty_listing']['file'] = $task['file'];
  }
  else {
    variable_set('drealty_drealty_listing_disabled', TRUE);
    // The enable-function sets a global, so the message is only displayed when
    // trying to enable the panel, not on every cache-clear.
    if (!empty($GLOBALS['drealty_enabling_drealty_listing'])) {
      drupal_set_message(t('Drealty module can not enable the listings panel page because some other module has already overridden it with %callback', array('%callback' => $page_callback)), 'warning');
    }
    return;
  }
}

/**
 * The enable/disable callback for drealty_listing overrides.
 */
function drealty_drealty_listing_enable($cache, $status) {
  variable_set('drealty_drealty_listing_disabled', $status);
  // Set a global flag so that the menu routine knows it needs to set a message
  // if enabling cannot be done.
  if (!$status) {
    $GLOBALS['drealty_enabling_drealty_listing'] = TRUE;
  }
}

/**
 * Callback to get arguments provided by this task handler.
 *
 * Since this is the listing view and there is no UI on the arguments, we
 * create dummy arguments that contain the needed data.
 */
function drealty_drealty_listing_get_arguments($task, $subtask_id) {
  return array(
    array(
      'keyword' => 'drealty_listing',
      'identifier' => t('Drealty listing being viewed'),
      'id' => 1,
      'name' => 'entity_id:drealty_listing',
      'setttings' => array(),
    ),
  );
}

/**
 * Callback to get context placeholders provided by this handler.
 */
function drealty_drealty_listing_get_contexts($task, $subtask_id) {
  return ctools_context_get_placeholders_from_argument(drealty_drealty_listing_get_arguments($task, $subtask_id));
}

/**
 * Entry point for our overridden drealty_listing URL.
 *
 * This function asks its assigned handlers who, if anyone, would like to run
 * with it. If no one does, it passes through to drealty's handler.
 */
function drealty_drealty_listing_page($drealty_listing) {
  // Load my task plugin.
  $task = page_manager_get_task('drealty_listing');

  // Load the listing into a context.
  ctools_include('context');
  ctools_include('context-task-handler');
  $contexts = ctools_context_handler_get_task_contexts($task, '', array($drealty_listing));
  $args = array($drealty_listing->id);

  $output = ctools_context_handler_render($task, '', $contexts, $args);
  if ($output !== FALSE) {
    return $output;
  }

  // Fallback to the default listings handler.
  $function = 'drealty_listing_view';
  $args = array($drealty_listing);

  foreach (module_implements('page_manager_override') as $module) {
    $callback = $module . '_page_manager_override';
    if (($rc = $callback('drealty_listing')) && function_exists($rc)) {
      $function = $rc;
      break;
    }
  }

  return $function($drealty_listing);
}
