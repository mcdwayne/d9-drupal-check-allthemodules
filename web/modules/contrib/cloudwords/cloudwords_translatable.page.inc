<?php

/**
 * @file
 * Contains cloudwords_translatable.page.inc.
 *
 * Page callback for Cloudwords translatable entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Cloudwords translatable templates.
 *
 * Default template: cloudwords_translatable.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_cloudwords_translatable(array &$variables) {
  // Fetch CloudwordsTranslatable Entity Object.
  $cloudwords_translatable = $variables['elements']['#cloudwords_translatable'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
