<?php

/**
 * @file
 * Contains slides_slide.page.inc..
 *
 * Page callback for Slide entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Slide templates.
 *
 * Default template: slides_slide.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_slides_slide(array &$variables) {
  // Fetch Slide Entity Object.
  $slides_slide = $variables['elements']['#slides_slide'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
