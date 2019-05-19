<?php

/**
 * @file
 * Contains user_points.page.inc.
 *
 * Page callback for User points entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for User points templates.
 *
 * Default template: user_points.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_user_points(array &$variables) {
  // Fetch UserPoints Entity Object.
  $user_points = $variables['elements']['#user_points'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
