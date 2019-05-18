<?php

/**
 * @file
 * Contains cloud_config.page.inc.
 *
 * Page callback for Cloud config entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Cloud config templates.
 *
 * Default template: cloud_config.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_cloud_config(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
