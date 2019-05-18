<?php

/**
 * @file
 * Contains ipfs_hash.page.inc.
 *
 * Page callback for IPFSHash entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for IPFSHash templates.
 *
 * Default template: ipfs_hash.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_ipfs_hash(array &$variables) {
  // Fetch IPFSHash Entity Object.
  $ipfs_hash = $variables['elements']['#ipfs_hash'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
