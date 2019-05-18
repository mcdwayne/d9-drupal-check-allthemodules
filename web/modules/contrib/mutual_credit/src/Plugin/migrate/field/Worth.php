<?php

namespace Drupal\mcapi\Plugin\migrate\field;

use Drupal\migrate_drupal\Plugin\migrate\field\FieldPluginBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * @MigrateField(
 *   id = "worth",
 *   core = {7},
 *   source_module = "mcapi",
 *   destination_module = "mcapi",
 *   type_map = {
 *    "worth_field" = "worth"
 *   }
 * )
 */
class Worth extends FieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'worth_field' => 'worth',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldWidgetMap() {
    return [
      'worths_widget' => 'worth',
    ];
  }

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
       'plugin' => 'mcapi_worth',
       'source' => $field_name
     ];
  }
}
