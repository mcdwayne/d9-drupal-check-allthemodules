<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\field\commerce1;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Commerce price migrate field plugin.
 *
 * @MigrateField(
 *   id = "commerce_price",
 *   core = {7},
 *   source_module = "commerce_price",
 *   destination_module = "commerce_price"
 * )
 */
class CommercePrice extends FieldPluginBase {

  /**
   * Field name map.
   *
   * @var array
   *   The field names on orders are different in Commerce 2 than Commerce 1.
   */
  public $fieldNameMap =
    [
      // The order total is now an Order total_price.
      'commerce_order_total' => 'total_price',
      // Line item total is now an Order Item total_price.
      'commerce_total' => 'total_price',
      'commerce_unit_price' => 'unit_price',
    ];

  /**
   * {@inheritdoc}
   */
  public function getFieldType(Row $row) {
    return 'commerce_price';
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $destination_field_name = isset($this->fieldNameMap[$field_name]) ? $this->fieldNameMap[$field_name] : $field_name;
    $process = [
      'plugin' => 'commerce1_migrate_commerce_price',
      'source' => $field_name,
    ];
    $migration->setProcessOfProperty($destination_field_name, $process);
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    $this->defineValueProcessPipeline($migration, $field_name, $data);
  }

}
