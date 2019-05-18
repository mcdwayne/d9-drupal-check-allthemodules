<?php

/**
 * @file
 * Contains dcat_vcard.page.inc.
 *
 * Page callback for vCard entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for vCard templates.
 *
 * Default template: dcat_vcard.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_dcat_vcard(array &$variables) {
  // Fetch DcatVcard Entity Object.
  $dcat_vcard = $variables['elements']['#dcat_vcard'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
