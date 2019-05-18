<?php

/**
 * @file
 * Contains page callbacks, form validation, and form submission handlers.
 */

/**
 * Page callback: Outputs recipe nodes in various formats.
 *
 * Generates various representation of a recipe page with all descendants and
 * prints the requested representation to output.
 *
 * @param string $type
 *   The type of output requested.
 * @param int $nid
 *   The node ID of the node to export.
 */
function recipe_export($type = 'html', $nid = NULL, $yield = NULL) {
  // normalize typed urls
  $type = drupal_strtolower($type);

  // load supported formats
  $formats = module_invoke_all('recipeio', 'export_single');
  $perm = isset($formats[$type]['access arguments']) ? $formats[$type]['access arguments'] : 'export recipes';
  if (!user_access($perm)) {
    drupal_access_denied();
    return;
  }

  // If callback exists, call it, otherwise error out.
  if (isset($formats[$type]) && function_exists($formats[$type]['callback'])) {
    echo call_user_func($formats[$type]['callback'], $nid, $yield);
  }
  else {
    drupal_set_message(t('Unknown export format(%the_format).', array('%the_format' => $type)), 'error');
    drupal_not_found();
  }
}
