<?php

/**
 * @file
 * Hooks specific to the "oil.js" module.
 */

/**
 * @addtogroup hooks
 * @{
 * Hooks that extend the "oil.js" module.
 */

/**
 * Take control of path exclusion.
 *
 * @param bool $excluded
 *   Whether this path is excluded from cookie compliance behavior.
 * @param string $path
 *   Current string path.
 * @param string $exclude_paths
 *   Admin variable of excluded paths.
 */
function hook_oiljs_path_match_alter(&$excluded, $path, $exclude_paths) {
  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node && $node->type === 'my_type') {
    $excluded = TRUE;
  }
}

/**
 * Override oil.js configuration data.
 *
 * @param array $config_data
 *   The oil.js configuration.
 */
function hook_oiljs_configuration(&$config_data) {
  if (1 === \Drupal::currentUser()->id()) {
    // Disable cookie banner for user 1.
    $config_data['banner_enabled'] = FALSE;
  }
  $config_data['label_intro_heading'] = t('Hey, we are using cookies!');
}

/**
 * @} End of "addtogroup hooks".
 */
