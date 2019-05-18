<?php

/**
 * @file
 * Contains role_watchdog.page.inc.
 *
 * Page callback for Role Watchdog entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Role Watchdog templates.
 *
 * Default template: role_watchdog.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_role_watchdog(array &$variables) {
  // Fetch RoleWatchdog Entity Object.
  $role_watchdog = $variables['elements']['#role_watchdog'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
