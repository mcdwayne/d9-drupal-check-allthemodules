<?php

/**
 * @file
 * Contains entreprise.page.inc.
 *
 * Page callback for Entreprise entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Entreprise templates.
 *
 * Default template: entreprise.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_entreprise(array &$variables) {
  // Fetch Entreprise Entity Object.
  $entreprise = $variables['elements']['#entreprise'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
