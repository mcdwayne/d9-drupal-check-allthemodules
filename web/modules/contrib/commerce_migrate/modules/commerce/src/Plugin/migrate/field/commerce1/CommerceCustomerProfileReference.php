<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\field\commerce1;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;

/**
 * Field migration for the Customer Profile Reference field.
 *
 * @MigrateField(
 *   id = "commerce_customer_profile_reference",
 *   type_map = {
 *     "commerce_customer_profile_reference" = "entity_reference"
 *   },
 *   core = {7},
 *   source_module = "commerce_customer",
 *   destination_module = "profile"
 * )
 */
class CommerceCustomerProfileReference extends FieldPluginBase {

  /**
   * Field name map.
   *
   * @var array
   *   The field names on orders are different in Commerce 2 than Commerce 1.
   */
  public $fieldNameMap =
    [
      'commerce_customer_billing' => 'billing_profile',
      'commerce_customer_shipping' => 'shipping_profile',
    ];

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
    $destination_field_name = isset($this->fieldNameMap[$field_name]) ? $this->fieldNameMap[$field_name] : $field_name;
    $process = [
      'plugin' => 'commerce_migrate_commerce_reference_revision',
      'migration' => 'commerce1_profile',
      'source' => $field_name,
      'no_stub' => TRUE,
    ];
    $migration->setProcessOfProperty($destination_field_name, $process);
  }

}
