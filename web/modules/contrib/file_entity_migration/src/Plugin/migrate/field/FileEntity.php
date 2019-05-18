<?php

namespace Drupal\file_entity_migration\Plugin\migrate\field;

use Drupal\Core\Field\Plugin\migrate\field\d7\EntityReference;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Field Plugin for file_entity migrations.
 *
 * @MigrateField(
 *   id = "file_entity",
 *   core = {7},
 *   type_map = {
 *     "file_entity" = "entity_reference",
 *   },
 *   source_module = "file",
 *   destination_module = "core",
 * )
 */
class FileEntity extends EntityReference {

  /**
   * {@inheritdoc}
   */
  public function processField(MigrationInterface $migration) {
    $settings = [
      'file_entity' => [
        'plugin' => 'file_entity_field_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('settings', $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldInstance(MigrationInterface $migration) {
    $settings = [
      'file_entity' => [
        'plugin' => 'file_entity_field_instance_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('settings', $settings);
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldValues(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'target_id' => 'fid',
      ],
    ];

    $migration->setProcessOfProperty($field_name, $process);
  }

  /**
   * {@inheritdoc}
   */
  public function processFieldFormatter(MigrationInterface $migration) {
    $settings = [
      'file_entity' => [
        'plugin' => 'file_entity_field_formatter_settings',
      ],
    ];
    $migration->setProcessOfProperty('options/settings', $settings);
    parent::processFieldFormatter($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    return [
      'file_image_picture' => 'media_responsive_thumbnail',
      'file_image_image' => 'media_thumbnail',
      'file_rendered' => 'entity_reference_entity_view',
    ] + parent::getFieldFormatterMap();
  }

}
