<?php

/**
 * @file
 * Contains commerce_inventory_item.page.inc.
 *
 * Page callback for Inventory Item entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Inventory Item templates.
 *
 * Default template: commerce_inventory_item.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_commerce_inventory_item(array &$variables) {
  /** @var \Drupal\commerce_inventory\Entity\InventoryItemInterface $commerce_inventory_item */
  // Fetch InventoryItem Entity Object.
  $commerce_inventory_item = $variables['elements']['#commerce_inventory_item'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  $variables['content']['activity'] = [
    '#type' => 'view',
    '#name' => 'commerce_activity',
    '#display_id' => 'default',
    '#arguments' => [$commerce_inventory_item->id(), 'commerce_inventory_item'],
    '#embed' => TRUE,
    '#title' => t('Inventory activity'),
  ];
}
