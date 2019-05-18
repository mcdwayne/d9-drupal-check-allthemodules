<?php

/**
 * @file
 * Contains cronpub_entity.page.inc..
 *
 * Page callback for Cronpub Task entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Cronpub Task templates.
 *
 * Default template: cronpub_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_cronpub_entity(array &$variables) {
  // Fetch CronpubEntity Entity Object.
  $cronpub_entity = $variables['elements']['#cronpub_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
