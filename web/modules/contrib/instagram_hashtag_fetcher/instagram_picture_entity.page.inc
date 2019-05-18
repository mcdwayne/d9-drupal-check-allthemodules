<?php

/**
 * @file
 * Contains instagram_picture_entity.page.inc.
 *
 * Page callback for Instagram Picture Entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Instagram Picture Entity templates.
 *
 * Default template: instagram_picture_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_instagram_picture_entity(array &$variables) {
  // Fetch InstagramPictureEntity Entity Object.
  $instagram_picture_entity = $variables['elements']['#instagram_picture_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
