<?php

/**
 * @file
 * Contains commerce_inventory_location.page.inc.
 *
 * Page callback for Inventory Location entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for Inventory Location templates.
 *
 * Default template: commerce_inventory_location.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_commerce_inventory_location(array &$variables) {
  // Fetch InventoryLocation Entity Object.
  /** @var \Drupal\commerce_inventory\Entity\InventoryLocationInterface $commerce_inventory_location */
  $commerce_inventory_location = $variables['elements']['#commerce_inventory_location'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  /** @var \Drupal\commerce_inventory\Entity\Storage\InventoryItemStorageInterface $item_storage */
  $item_storage = \Drupal::entityTypeManager()->getStorage('commerce_inventory_item');
  $item_ids = $item_storage->getItemIdsByLocation($commerce_inventory_location->id());

  // Make sure the table shows up even without items at the location.
  $item_ids[] = 0;

  $variables['content']['activity'] = [
    '#type' => 'view',
    '#name' => 'commerce_inventory_commerce_log',
    '#display_id' => 'commerce_inventory_location',
    '#arguments' => [implode('+', $item_ids), 'commerce_inventory_item'],
    '#embed' => TRUE,
    '#title' => t('Inventory activity'),
  ];
}
