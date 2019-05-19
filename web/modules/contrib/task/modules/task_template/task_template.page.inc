<?php

/**
 * @file
 * Contains task_template.page.inc.
 *
 * Page callback for Task Template entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Task Template templates.
 *
 * Default template: task_template.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_task_template(array &$variables) {
  // Fetch TaskTemplate Entity Object.
  $task_template = $variables['elements']['#task_template'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
