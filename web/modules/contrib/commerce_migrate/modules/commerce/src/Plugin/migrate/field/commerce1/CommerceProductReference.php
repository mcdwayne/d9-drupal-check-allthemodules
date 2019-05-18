<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\field\commerce1;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Field migration for commerce_product reference.
 *
 * @MigrateField(
 *   id = "commerce_product_reference",
 *   type_map = {
 *     "commerce_product_reference" = "entity_reference"
 *   },
 *   core = {7},
 *   source_module = "commerce_product_reference",
 *   destination_module = "commerce_product"
 * )
 */
class CommerceProductReference extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    $this->defineValueProcessPipeline($migration, $field_name, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'target_id' => 'product_id',
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }

}
