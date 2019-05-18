<?php

/**
 * @file
 * Contains commerce_installment_plan.page.inc.
 *
 * Page callback for Installment Plan entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Installment Plan templates.
 *
 * Default template: commerce_installment_plan.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_commerce_installment_plan(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
