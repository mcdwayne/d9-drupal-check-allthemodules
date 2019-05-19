<?php

/**
 * @file
 * Contains task_note.page.inc.
 *
 * Page callback for Task Note entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Task Note templates.
 *
 * Default template: task_note.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_task_note(array &$variables) {
  // Fetch TaskNote Entity Object.
  $task_note = $variables['elements']['#task_note'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
