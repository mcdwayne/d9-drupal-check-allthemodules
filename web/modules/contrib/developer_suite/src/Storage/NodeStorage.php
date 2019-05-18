<?php

namespace Drupal\developer_suite\Storage;

use Drupal\node\NodeStorage as CoreNodeStorage;

/**
 * Class NodeStorage.
 *
 * Changes the Node entity class to a custom class provided in a
 * EntityTypeClass plugin.
 *
 * @package Drupal\developer_suite\Storage
 */
abstract class NodeStorage extends CoreNodeStorage {

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    $this->entityClass = $this->getEntityTypeClass($values['type']);

    return parent::doCreate($values);
  }

  /**
   * Returns the entity class per node type.
   *
   * @param string $type
   *   The node type.
   *
   * @return mixed
   *   The entity class.
   */
  abstract public function getEntityTypeClass($type);

  /**
   * {@inheritdoc}
   */
  protected function mapFromStorageRecords(array $records, $loadFromRevision = FALSE) {
    $nodeStorageRecords = [];
    $result = [];

    foreach ($records as $id => $record) {
      $nodeStorageRecords[$this->getEntityTypeClass($record->type)][$id] = $record;
    }

    foreach ($nodeStorageRecords as $nodeStorageClass => $nodeStorageRecord) {
      $this->entityClass = $nodeStorageClass;
      $parentResult = parent::mapFromStorageRecords($nodeStorageRecord, $loadFromRevision);

      foreach ($parentResult as $parentId => $parentRecord) {
        $result[$parentId] = $parentRecord;
      }
    }

    return $result;
  }

}
