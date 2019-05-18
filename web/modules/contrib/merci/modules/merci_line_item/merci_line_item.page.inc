<?php

/**
 * @file
 * Contains merci_line_item.page.inc.
 *
 * Page callback for Merci Line Item entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Merci Line Item templates.
 *
 * Default template: merci_line_item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_merci_line_item(array &$variables) {
  // Fetch MerciLineItem Entity Object.
  $merci_line_item = $variables['elements']['#merci_line_item'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
