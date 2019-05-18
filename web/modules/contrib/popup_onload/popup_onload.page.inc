<?php

/**
 * @file
 * Contains popup_onload.page.inc.
 *
 * Page callback for Popup On Load entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Popup On Load templates.
 *
 * Default template: popup_onload.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_popup_onload(array &$variables) {
  // Fetch PopupOnLoad Entity Object.
  $popup_onload = $variables['elements']['#popup_onload'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
