<?php

/**
 * @file
 * Contains dcat_agent.page.inc.
 *
 * Page callback for Agent entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Agent templates.
 *
 * Default template: dcat_agent.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_dcat_agent(array &$variables) {
  // Fetch DcatAgent Entity Object.
  $dcat_agent = $variables['elements']['#dcat_agent'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
