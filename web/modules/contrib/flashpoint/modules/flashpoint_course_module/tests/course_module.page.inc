<?php

/**
 * @file
 * Contains course_module.page.inc.
 *
 * Page callback for Course module entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Course module templates.
 *
 * Default template: course_module.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_course_module(array &$variables) {
  // Fetch CourseModule Entity Object.
  $course_module = $variables['elements']['#course_module'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
