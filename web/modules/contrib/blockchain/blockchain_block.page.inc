<?php

/**
 * @file
 * Contains blockchain_block.page.inc.
 *
 * Page callback for Blockchain Block entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Blockchain Block templates.
 *
 * Default template: blockchain_block.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_blockchain_block(array &$variables) {
  // Fetch BlockchainBlock Entity Object.
  $blockchain_block = $variables['elements']['#blockchain_block'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
