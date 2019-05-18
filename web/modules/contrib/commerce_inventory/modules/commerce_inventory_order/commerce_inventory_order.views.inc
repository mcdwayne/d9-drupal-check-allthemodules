<?php

/**
 * @file
 * Contains commerce_inventory\commerce_inventory.views.inc..
 *
 * Provide a custom views field data that isn't tied to any other module.
 */

use Drupal\commerce_inventory\InventoryHelper;

/**
 * Implements hook_views_data_alter().
 */
function commerce_inventory_order_views_data_alter(array &$data) {
  /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $field_manager */
  $field_manager = Drupal::service('entity_field.manager');
  $fields = $field_manager->getBaseFieldDefinitions('commerce_order_item');
  InventoryHelper::alterFieldTypeViewsData($data, $fields['inventory_adjustment_holds']->getFieldStorageDefinition());
}
