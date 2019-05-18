<?php

/**
 * @file
 * Contains owlcarousel2.page.inc.
 *
 * Page callback for OwlCarousel2 entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for OwlCarousel2 templates.
 *
 * Default template: owlcarousel2.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_owlcarousel2(array &$variables) {
  // Fetch OwlCarousel2 Entity Object.
  // $owlcarousel2 = $variables['elements']['#owlcarousel2'];
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
