<?php

/**
 * @file
 * Contains swish_transaction.page.inc.
 *
 * Page callback for Swish transaction entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Swish transaction templates.
 *
 * Default template: swish_transaction.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_swish_transaction(array &$variables) {
  // Fetch SwishTransaction Entity Object.
  $swish_transaction = $variables['elements']['#swish_transaction'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
