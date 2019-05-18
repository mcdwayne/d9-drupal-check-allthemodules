<?php

/**
 * @file
 * Contains commerce_inventory\commerce_inventory.views.inc..
 *
 * Provide a custom views field data that isn't tied to any other module.
 */

use Drupal\commerce_inventory\InventoryHelper;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\field\FieldStorageConfigInterface;

/**
 * Implements hook_field_views_data_alter().
 */
function commerce_inventory_field_views_data_alter(array &$data, FieldStorageConfigInterface $field_storage) {
  InventoryHelper::alterFieldTypeViewsData($data, $field_storage);
}

/**
 * Implements hook_views_data_alter().
 */
function commerce_inventory_views_data_alter(array &$data) {
  $entity_types = Drupal::entityTypeManager()->getDefinitions();
  $options = array_filter($entity_types, function (EntityTypeInterface $entity_type) {
    return $entity_type->entityClassImplements('\Drupal\commerce\PurchasableEntityInterface');
  });

  foreach ($options as $entity_type_id => $entity_type) {
    if ($entity_type->entityClassImplements('\Drupal\commerce\PurchasableEntityInterface')) {
      $data[$entity_type_id]['create_inventory_item'] = [
        'title' => t('Create Inventory Item Button'),
        'help' => t('Add a form element that lets you create an inventory item from the purchasable entity and a location.'),
        'field' => [
          'id' => 'create_inventory_item',
        ],
      ];
      $data[$entity_type_id]['commerce_inventory_location']['argument'] = [
        'id' => 'purchasable_entity_location_id',
        'title' => t('Inventory Location'),
        'numeric' => TRUE,
        'validate type' => 'id',
      ];
    }
  }

  $data['commerce_log']['source_label'] = [
    'title' => t('Source label'),
    'help' => t('Add a label to the source entity.'),
    'field' => [
      'id' => 'commerce_log_source_label',
    ],
  ];

  $data['commerce_inventory_adjustment']['commerce_inventory_adjustment_type']['field']['id'] = 'commerce_inventory_adjustment_type_label';
}
