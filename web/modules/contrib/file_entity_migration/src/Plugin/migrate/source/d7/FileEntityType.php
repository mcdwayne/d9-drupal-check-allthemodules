<?php

namespace Drupal\file_entity_migration\Plugin\migrate\source\d7;

use Drupal\paragraphs\Plugin\migrate\source\DrupalSqlBase;

/**
 * File Entity Type source plugin.
 *
 * @MigrateSource(
 *   id = "d7_file_entity_type",
 *   source_module = "file_entity"
 * )
 */
class FileEntityType extends DrupalSqlBase {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('file_managed', 'fm')
      ->distinct()
      ->fields('fm', ['type'])
      ->condition('fm.status', TRUE);
    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'type' => $this->t('File Entity type machine name'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['type']['type'] = 'string';
    return $ids;
  }

}
