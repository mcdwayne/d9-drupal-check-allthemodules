<?php

namespace Drupal\media_migration\Plugin\migrate\source\d7;

use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;

/**
 * File Entity Item source plugin.
 *
 * Available configuration keys:
 * - type: (optional) If supplied, this will only return fields
 *   of that particular type.
 *
 * @MigrateSource(
 *   id = "d7_file_entity_item",
 *   source_module = "file_entity",
 * )
 */
class FileEntityItem extends FieldableEntity {

  /**
   * {@inheritdoc}
   */
  public function query() {
    $query = $this->select('file_managed', 'f')
      ->fields('f')
      ->orderBy('f.timestamp')
      ->condition('uri', 'public://%', 'LIKE');

    // Filter by type, if configured.
    if (isset($this->configuration['type'])) {
      $query->condition('type', $this->configuration['type']);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Get Field API field values.
    foreach (array_keys($this->getFields('file', $row->getSourceProperty('type'))) as $field) {
      $fid = $row->getSourceProperty('fid');
      $row->setSourceProperty($field, $this->getFieldValues('file', $field, $fid));
    }

    // Add width and height source properties for image entities.
    if ($row->getSourceProperty('type') == 'image') {
      $result = $this->getDatabase()->query('select name, value from {file_metadata} where fid = :fid and name in (\'width\', \'height\')', [
        ':fid' => $row->getSourceProperty('fid'),
      ]);
      foreach ($result as $result_row) {
        $row->setSourceProperty($result_row->name, unserialize($result_row->value));
      }
    }

    return parent::prepareRow($row);
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    $fields = [
      'fid' => $this->t('The file identifier'),
      'uid' => $this->t('The user identifier'),
      'filename' => $this->t('The file name'),
      'uri' => $this->t('The URI of the file'),
      'filemime' => $this->t('The file mimetype'),
      'filesize' => $this->t('The file size'),
      'status' => $this->t('The file status'),
      'timestamp' => $this->t('The time that the file was added'),
      'type' => $this->t('The file type'),
      'created' => $this->t('The created timestamp'),
      'published' => $this->t('The published timestamp'),
      'promote' => $this->t('The promoted flag'),
      'sticky' => $this->t('The sticky flag'),
      'vid' => $this->t('The vid'),
    ];

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['fid']['type'] = 'integer';
    return $ids;
  }

}
