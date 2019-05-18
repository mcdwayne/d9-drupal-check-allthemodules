<?php

namespace Drupal\media_migration\Plugin\migrate\field;

use Drupal\Core\Field\Plugin\migrate\field\d7\EntityReference;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Field Plugin for image field to media image field migrations.
 *
 * @MigrateField(
 *   id = "media_image",
 *   core = {7},
 *   type_map = {
 *     "media_image" = "entity_reference",
 *   },
 *   source_module = "image",
 *   destination_module = "media",
 * )
 */
class MediaImage extends EntityReference {

  /**
   * {@inheritdoc}
   */
  public function alterFieldMigration(MigrationInterface $migration) {
    $settings = [
      'media_image' => [
        'plugin' => 'media_image_field_settings',
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
      'media_image' => [
        'plugin' => 'media_image_field_instance_settings',
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
  public function getFieldWidgetMap() {
    // No mapping. We let each site to define this.
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldFormatterMap() {
    // No mapping. We let each site to define this.
    return [];
  }

}
