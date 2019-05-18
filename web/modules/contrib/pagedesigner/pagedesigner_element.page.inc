<?php

/**
 * @file
 * Contains pagedesigner_element.page.inc.
 *
 * Page callback for Pagedesigner Element entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Pagedesigner Element templates.
 *
 * Default template: pagedesigner_element.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_pagedesigner_element(array &$variables) {
  // Fetch Element Entity Object.
  $pagedesigner_element = $variables['elements']['#pagedesigner_element'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
