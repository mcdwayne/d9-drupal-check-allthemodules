<?php

/**
 * @file
 * Contains quiz_results.page.inc.
 *
 * Page callback for Quiz results entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Quiz results templates.
 *
 * Default template: quiz_results.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_quiz_results(array &$variables) {
  // Fetch QuizResults Entity Object.
  $quiz_results = $variables['elements']['#quiz_results'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
