<?php

/**
 * @file
 * Contains simple_mega_menu.page.inc.
 *
 * Page callback for Simple mega menu entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Simple mega menu templates.
 *
 * Default template: simple_mega_menu.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_simple_mega_menu(array &$variables) {
  // Fetch SimpleMegaMenu Entity Object.
  /** @var \Drupal\simple_megamenu\Entity\SimpleMegaMenu $simple_mega_menu */
  $simple_mega_menu = $variables['elements']['#simple_mega_menu'];

  $variables['view_mode'] = $variables['elements']['#view_mode'];
  $variables['bundle'] = $simple_mega_menu->bundle();
  $variables['simple_mega_menu'] = $simple_mega_menu;

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

}
