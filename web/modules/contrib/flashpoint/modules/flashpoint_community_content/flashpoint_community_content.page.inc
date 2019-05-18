<?php

/**
 * @file
 * Contains flashpoint_community_content.page.inc.
 *
 * Page callback for Flashpoint community content entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Flashpoint community content templates.
 *
 * Default template: flashpoint_community_content.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_flashpoint_community_content(array &$variables) {
  // Fetch FlashpointCommunityContent Entity Object.
  $flashpoint_community_content = $variables['elements']['#flashpoint_community_content'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
