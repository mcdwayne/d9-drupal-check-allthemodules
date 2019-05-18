<?php

/**
 * @file
 * Hooks specific to the EU Cookie Compliance module.
 */

/**
 * @addtogroup hooks
 * @{
 * Hooks that extend the Cookiebot module.
 */

/**
 * Take control of Cookiebot path exclusion.
 *
 * @param bool $excluded
 *   Whether this path is excluded from cookie compliance behavior.
 * @param string $path
 *   Current string path.
 * @param string $exclude_paths
 *   Admin variable of excluded paths.
 */
function hook_cookiebot_path_match_alter(&$excluded, $path, $exclude_paths) {
  $node = \Drupal::routeMatch()->getParameter('node');
  if ($node && $node->type === 'my_type') {
    $excluded = TRUE;
  }
}

/**
 * @} End of "addtogroup hooks".
 */
