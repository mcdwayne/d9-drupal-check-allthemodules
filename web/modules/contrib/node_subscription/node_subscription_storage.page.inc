<?php

/**
 * @file
 * Contains node_subscription_storage.page.inc.
 *
 * Page callback for Node subscription entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Node subscription templates.
 *
 * Default template: node_subscription_storage.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_node_subscription_storage(array &$variables) {
  // Fetch NodeSubscriptionStorage Entity Object.
  $node_subscription_storage = $variables['elements']['#node_subscription_storage'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
