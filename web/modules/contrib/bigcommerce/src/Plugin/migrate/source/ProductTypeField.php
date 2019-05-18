<?php

namespace Drupal\bigcommerce\Plugin\migrate\source;

/**
 * Gets all Product Type Fields.
 *
 * @MigrateSource(
 *   id = "bigcommerce_product_type_field"
 * )
 */
class ProductTypeField extends ProductType {

  /**
   * {@inheritdoc}
   */
  public function getYield(array $params) {
    foreach ($this->getProductTypes() as $type) {
      foreach ($this->getProductTypeFields() as $field) {
        $field['product_type'] = $type['name'];
        yield $field;
      }
      // If this is the field storage, only pass one set of field.
      if ($this->configuration['import_type'] === 'storage') {
        return;
      }
    }
  }

  /**
   * Get all the product type fields.
   *
   * @return array
   *   The list of fields.
   */
  protected function getProductTypeFields() {
    return [
      'field_product_images' => [
        'field_name' => 'field_product_image',
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
      ],
      'field_product_brand' => [
        'field_name' => 'field_product_brand',
        'label' => 'Brand',
        'type' => 'entity_reference',
        'required' => FALSE,
        'cardinality' => 1,
        'storage_settings' => [
          'target_type' => 'taxonomy_term',
        ],
        'instance_settings' => [
          'handler' => 'default:taxonomy_term',
          'handler_settings' => [
            'target_bundles' => [
              'bigcommerce_product_brand' => 'bigcommerce_product_brand',
            ],
            'sort' => [
              'field' => 'name',
              'direction' => 'asc',
            ],
            'auto_create' => FALSE,
            'auto_create_bundle' => NULL,
          ],
        ],
      ],
      'field_product_category' => [
        'field_name' => 'field_product_category',
        'label' => 'Categories',
        'type' => 'entity_reference',
        'required' => FALSE,
        'cardinality' => -1,
        'storage_settings' => [
          'target_type' => 'taxonomy_term',
        ],
        'instance_settings' => [
          'handler' => 'default:taxonomy_term',
          'handler_settings' => [
            'target_bundles' => [
              'bigcommerce_product_category' => 'bigcommerce_product_category',
            ],
            'sort' => [
              'field' => 'name',
              'direction' => 'asc',
            ],
            'auto_create' => FALSE,
            'auto_create_bundle' => NULL,
          ],
        ],
      ],
    ];
  }

}
