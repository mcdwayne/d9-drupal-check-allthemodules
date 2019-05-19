<?php

/**
 * @file
 * Contains workflow_task.page.inc.
 *
 * Page callback for Workflow task entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Workflow task templates.
 *
 * Default template: workflow_task.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_workflow_task(array &$variables) {
  // Fetch WorkflowTask Entity Object.
  $workflow_task = $variables['elements']['#workflow_task'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
