<?php

/**
 * @file
 * Hooks provided by the overview_field module.
 */

/**
 * @addtogroup hooks
 * @{
 */

/**
 * Adds an option to the overview field.
 *
 * @param array $options
 *   Contains all the options for the overview field.
 */
function hook_overview_field_options_alter(array &$options) {
  $options['recent_content'] = t('Show a list of the recent content on the site');
}

/**
 * Defines what should be returned in the overview.
 *
 * @param string $key
 *   The key defined in hook_overview_options_alter().
 * @param array $output
 *   A renderable array of data, that needs to be displayed in the field.
 */
function hook_overview_field_output_alter($key, array &$output) {
  if ($key == 'recent_content') {
    // Use OverviewFieldFormatter->loadView('content_recent', 'block_1'); to
    // load views.
    $output = t('Load your custom overview or load a view.');
  }
}

/**
 * @} End of "addtogroup hooks".
 */
