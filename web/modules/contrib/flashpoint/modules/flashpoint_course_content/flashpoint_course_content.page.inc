<?php

/**
 * @file
 * Contains flashpoint_course_content.page.inc.
 *
 * Page callback for Flashpoint course content entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Flashpoint course content templates.
 *
 * Default template: flashpoint_course_content.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_flashpoint_course_content(array &$variables) {
  // Fetch FlashpointCourseContent Entity Object.
  $flashpoint_course_content = $variables['elements']['#flashpoint_course_content'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
