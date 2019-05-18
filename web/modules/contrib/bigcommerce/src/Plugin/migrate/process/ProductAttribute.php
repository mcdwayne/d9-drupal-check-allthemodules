<?php

namespace Drupal\bigcommerce\Plugin\migrate\process;

use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Plugin\migrate\process\MigrationLookup;
use Drupal\migrate\Row;

/**
 * Finds the attribute values and stores in a correctly named row destination.
 *
 * @MigrateProcessPlugin(
 *   id = "bigcommerce_product_attribute"
 * )
 */
class ProductAttribute extends MigrationLookup {

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {

    // Process Product Attributes.
    $option_values_ids = $row->getSourceProperty('option_values_ids');
    foreach ($option_values_ids as $option_values_id) {
      // TODO: Modify Product Attribute migration to use fieldname rather than
      // TODO: using the field source Name.

      $attribute_value_migration = $this->migrationPluginManager->createInstance('bigcommerce_product_attribute_value');
      $attribute_id = $attribute_value_migration->getIdMap()
        ->lookupDestinationId(['id' => $option_values_id]);
      if ($attribute_id) {
        $attribute_value = \Drupal::entityTypeManager()
          ->getStorage('commerce_product_attribute_value')
          ->load($attribute_id[0]);

        if ($attribute_value) {
          // Build the attribute field name.
          $variation_field_name = 'attribute_' . $attribute_value->getAttributeId();
          $new_value = parent::transform($option_values_id, $migrate_executable, $row, $destination_property);
          $row->setDestinationProperty($variation_field_name, $new_value);
        }
      }
    }
  }

}
