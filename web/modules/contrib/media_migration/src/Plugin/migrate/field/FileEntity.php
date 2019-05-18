<?php

namespace Drupal\media_migration\Plugin\migrate\field;

use Drupal\Core\Field\Plugin\migrate\field\d7\EntityReference;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Field Plugin for file_entity to media migrations.
 *
 * @MigrateField(
 *   id = "file_entity",
 *   core = {7},
 *   type_map = {
 *     "file_entity" = "entity_reference",
 *   },
 *   source_module = "file",
 *   destination_module = "media",
 * )
 */
class FileEntity extends EntityReference {

  /**
   * {@inheritdoc}
   */
  public function alterFieldMigration(MigrationInterface $migration) {
    $settings = [
      'file_entity' => [
        'plugin' => 'file_entity_field_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('settings', $settings);

    parent::alterFieldMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function alterFieldInstanceMigration(MigrationInterface $migration) {
    $settings = [
      'file_entity' => [
        'plugin' => 'file_entity_field_instance_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('settings', $settings);

    parent::alterFieldInstanceMigration($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
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
  public function alterFieldFormatterMigration(MigrationInterface $migration) {
    $settings = [
      'file_entity' => [
        'plugin' => 'file_entity_field_formatter_settings',
      ],
    ];
    $migration->mergeProcessOfProperty('options/settings', $settings);

    parent::alterFieldFormatterMigration($migration);
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
