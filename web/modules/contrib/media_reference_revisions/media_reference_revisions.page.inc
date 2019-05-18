<?php

/**
 * @file
 * Contains media_reference_revision.page.inc.
 *
 * Page callback for Media reference revisions entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Media reference revisions templates.
 *
 * Default template: media_reference_revision.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_media_reference_revision(array &$variables) {
  // Fetch MediaReferenceRevision Entity Object.
  $media_reference_revision = $variables['elements']['#media_reference_revision'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
