<?php

/**
 * @file
 * Contains phones_call.page.inc.
 *
 * Page callback for Phones call entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Phones call templates.
 *
 * Default template: phones_call.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_phones_call(array &$variables) {
  // Fetch PhonesCall Entity Object.
  $phones_call = $variables['elements']['#phones_call'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
