<?php

/**
 * @file
 * Contains eloqua_app_cloud_service.page.inc.
 *
 * Page callback for Eloqua AppCloud Service entities.
 */

use Drupal\Core\Render\Element;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Prepares variables for Eloqua AppCloud Service templates.
 *
 * Default template: eloqua_app_cloud_service.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_eloqua_app_cloud_service(array &$variables) {
  // Fetch EloquaAppCloudService Entity Object.
  $eloqua_app_cloud_service = $variables['elements']['#eloqua_app_cloud_service'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
