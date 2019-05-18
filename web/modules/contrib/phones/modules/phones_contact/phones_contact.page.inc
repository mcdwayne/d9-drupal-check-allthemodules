<?php

/**
 * @file
 * Contains phones_contact.page.inc.
 *
 * Page callback for Phones contact entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Phones contact templates.
 *
 * Default template: phones_contact.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_phones_contact(array &$variables) {
  // Fetch PhonesContact Entity Object.
  $phones_contact = $variables['elements']['#phones_contact'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
