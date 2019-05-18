<?php

/**
 * @file
 * Contains chat_channel_member.page.inc.
 *
 * Page callback for Chat channel member entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Chat channel member templates.
 *
 * Default template: chat_channel_member.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_chat_channel_member(array &$variables) {

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
