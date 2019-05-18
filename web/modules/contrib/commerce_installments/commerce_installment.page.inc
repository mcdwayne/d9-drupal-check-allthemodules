<?php

/**
 * @file
 * Contains commerce_installment.page.inc.
 *
 * Page callback for Installment entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Installment templates.
 *
 * Default template: commerce_installment.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_commerce_installment(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
