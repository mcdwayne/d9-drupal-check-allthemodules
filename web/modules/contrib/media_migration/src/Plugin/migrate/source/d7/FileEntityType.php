<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\DrupalSqlBase;

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
    // 'undefined' file type refers to files that file_entity could not
    // classify. Probably because there was an error during
    // its creation.
    $query = $this->select('file_managed', 'fm')
      ->distinct()
      ->fields('fm', ['type'])
      ->condition('fm.status', TRUE)
      ->condition('uri', 'public://%', 'LIKE')
      ->condition('fm.type', 'undefined', '<>');
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

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Don't migrate bundles which don't exist in the destination.
    $type = $row->getSourceProperty('type');
    if (!$this->entityManager->getStorage('media_type')->load($type)) {
      return FALSE;
    }

    return parent::prepareRow($row);
  }


}
