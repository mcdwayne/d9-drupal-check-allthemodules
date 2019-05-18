<?php

/**
 * @file
 * Contains membership_entity.page.inc.
 *
 * Page callback for Membership entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Membership templates.
 *
 * Default template: membership_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_membership_entity(array &$variables) {
  // Fetch MembershipEntity Entity Object.
  $membership_entity = $variables['elements']['#membership_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
