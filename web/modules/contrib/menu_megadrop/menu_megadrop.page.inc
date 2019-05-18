<?php

/**
 * @file
 * Contains menu_megadrop.page.inc.
 *
 * Page callback for Menu megadrop entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Menu megadrop templates.
 *
 * Default template: menu_megadrop.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_menu_megadrop(array &$variables) {
  // Fetch MenuMegadrop Entity Object.
  $menu_megadrop = $variables['elements']['#menu_megadrop'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
