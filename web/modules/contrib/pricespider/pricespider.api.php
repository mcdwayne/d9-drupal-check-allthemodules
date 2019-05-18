<?php

/**
 * @file
 * Hooks provided by the Price Spider module.
 */

use Drupal\Core\Entity\EntityInterface;

/**
 * Alters the list of entity types that are available as product types.
 *
 * @param array &$product_types
 *   An associative array of entity types keyed by type then bundle name
 *   followed by field used as SKU field.
 */
function hook_pricespider_product_types_alter(array &$product_types) {

  foreach ($product_types as $entity_type => &$bundles) {
    foreach ($bundles as $bundle_name => &$sku_field) {
      if ($entity_type == 'node' && $bundle_name == 'article') {
        $sku_field = 'title';
      }
    }
  }
}

/**
 * Alters the list of field types that are available as SKU fields.
 *
 * @param array &$field_types
 *   Array of Drupal field types.
 */
function hook_pricespider_sku_field_types_alter(array &$field_types) {
  if (!in_array('entityreference', $field_types)) {
    $field_types[] = 'entityreference';
  }
}

/**
 * Alters the value returned from the field marked as the SKU field for an entity.
 *
 * @param mixed &$field_value
 *   The value that has currently been retrieved.
 * @param \Drupal\Core\Entity\EntityInterface $entity
 *   The entity object.
 * @param string $field_name
 *   The name of the field that the value was retrieved from.
 */
function hook_pricespider_field_value(&$field_value, EntityInterface $entity, $field_name) {
  if ($entity->bundle() == 'article' && $field_name == 'title') {
    $field_value = strtoupper($field_value);
  }
}
