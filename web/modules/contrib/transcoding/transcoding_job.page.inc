<?php

/**
 * @file
 * Contains transcoding_job.page.inc.
 *
 * Page callback for Transcoding job entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Transcoding job templates.
 *
 * Default template: transcoding_job.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_transcoding_job(array &$variables) {
  // Fetch TranscodingJob Entity Object.
  $variables['transcoding_job'] = $variables['elements']['#transcoding_job'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
