<?php

/**
 * @file
 * Contains dcat_distribution.page.inc.
 *
 * Page callback for Distribution entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Distribution templates.
 *
 * Default template: dcat_distribution.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_dcat_distribution(array &$variables) {
  // Fetch DcatDistribution Entity Object.
  $dcat_distribution = $variables['elements']['#dcat_distribution'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
