<?php

namespace Drupal\bigcommerce\Plugin\migrate\source;

/**
 * Gets all Product Types from BigCommerce API.
 *
 * @MigrateSource(
 *   id = "bigcommerce_product_variation_type_field"
 * )
 */
class ProductVariationTypeField extends ProductVariationType {

  /**
   * {@inheritdoc}
   */
  public function getYield(array $params) {
    foreach ($this->getVariationTypes() as $type) {
      foreach ($this->getVariationTypeFields() as $field) {
        $field['bundle'] = $type['name'];
        yield $field;
      }

      // If this is the field storage, only pass one set of field.
      if ($this->configuration['import_type'] === 'storage') {
        return;
      }
    }
  }

  /**
   * Get all the product variation type fields.
   *
   * @return array
   *   The list of fields.
   */
  protected function getVariationTypeFields() {
    $fields = $this->getAttributeFields();

    // Add Image Field.
    $fields['field_variation_image'] = [
      'source_name' => 'field_variation_image',
      'field_name' => 'field_variation_image',
      'label' => 'Images',
      'type' => 'image',
      'required' => FALSE,
      'cardinality' => -1,
      'storage_settings' => [
        'target_type' => 'file',
        'default_image' => [
          'uuid' => NULL,
          'alt' => NULL,
          'title' => NULL,
          'width' => NULL,
          'height' => NULL,
        ],
      ],
      'instance_settings' => [
        'file_directory' => 'bigcommerce/product',
        'file_extensions' => 'png gif jpg jpeg',
        'alt_field' => TRUE,
        'alt_field_required' => TRUE,
        'title_field' => FALSE,
        'title_field_required' => FALSE,
        'handler' => 'default:file',
      ],
    ];

    return $fields;
  }

  /**
   * Get the product attribute fields to attach to the product variation types.
   *
   * @return array
   *   The list of fields.
   */
  protected function getAttributeFields() {
    static $fields = [];

    if (!empty($fields)) {
      return $fields;
    }

    // Load all attributes.
    $migration_plugin_manager = \Drupal::service('plugin.manager.migration');
    $attribute_migration = $migration_plugin_manager->createInstance('bigcommerce_product_attribute');
    $id_map = $attribute_migration->getIdMap();
    $db_attributes = $id_map->getDatabase()->select($id_map->mapTableName(), 'map')
      ->fields('map', ['sourceid1', 'destid1'])
      ->execute()
      ->fetchAllKeyed(1, 0);

    $attributes = \Drupal::entityTypeManager()->getStorage('commerce_product_attribute')->loadMultiple(array_keys($db_attributes));
    foreach ($attributes as $attribute) {
      $field_name = 'attribute_' . substr($attribute->id(), 0, 20);
      $fields[$field_name] = [
        'source_name' => $attribute->id(),
        'field_name' => $field_name,
        'label' => $attribute->label(),
        'type' => 'entity_reference',
        'required' => FALSE,
        'cardinality' => 1,
        'storage_settings' => [
          'target_type' => 'commerce_product_attribute_value',
        ],
        'instance_settings' => [
          'handler' => 'default:commerce_product_attribute_value',
          'handler_settings' => [
            'target_bundles' => [
              $attribute->id() => $attribute->id(),
            ],
            'sort' => [
              'field' => 'name',
              'direction' => 'asc',
            ],
            'auto_create' => FALSE,
            'auto_create_bundle' => NULL,
          ],
        ],
      ];
    }

    return $fields;
  }

}
