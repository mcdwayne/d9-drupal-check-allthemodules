<?php

/**
 * @file
 * Contains cloudwords_project.page.inc.
 *
 * Page callback for Cloudwords project entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Cloudwords project templates.
 *
 * Default template: cloudwords_project.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_cloudwords_project(array &$variables) {
  // Fetch CloudwordsProject Entity Object.
  $cloudwords_project = $variables['elements']['#cloudwords_project'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
