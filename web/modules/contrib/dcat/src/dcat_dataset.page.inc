<?php

/**
 * @file
 * Contains dcat_dataset.page.inc.
 *
 * Page callback for Dataset entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Dataset templates.
 *
 * Default template: dcat_dataset.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_dcat_dataset(array &$variables) {
  // Fetch DcatDataset Entity Object.
  $dcat_dataset = $variables['elements']['#dcat_dataset'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
