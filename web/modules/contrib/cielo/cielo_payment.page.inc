<?php

/**
 * @file
 * Contains cielo_payment.page.inc.
 *
 * Page callback for Cielo credit card payment entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Cielo credit card payment templates.
 *
 * Default template: cielo_payment.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_cielo_payment(array &$variables) {
  // Fetch CieloPayment Entity Object.
  $cielo_payment = $variables['elements']['#cielo_payment'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
