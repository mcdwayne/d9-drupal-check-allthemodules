<?php

/**
 * @file
 * Contains subscription_term.page.inc.
 *
 * Page callback for Subscription Term entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Subscription Term templates.
 *
 * Default template: subscription_term.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_subscription_term(array &$variables) {
  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
