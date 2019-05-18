<?php

namespace Drupal\entity_type_class\Storage;

use Drupal\file\FileStorage as CoreFileStorage;

/**
 * Class FileStorage.
 *
 * Changes the File entity class to a custom class provided in a
 * EntityClass plugin.
 *
 * @package Drupal\entity_type_class\Storage
 */
abstract class FileStorage extends CoreFileStorage {

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    $this->entityClass = $this->getEntityTypeClass();

    return parent::doCreate($values);
  }

  /**
   * Returns the entity class.
   *
   * @return mixed
   *   The entity class.
   */
  abstract public function getEntityTypeClass();

  /**
   * Maps from storage records to entity objects, and attaches fields.
   *
   * @param array $records
   *   Associative array of query results, keyed on the entity ID.
   * @param bool $loadFromRevision
   *   Flag to indicate whether revisions should be loaded or not.
   *
   * @return array
   *   An array of entity objects implementing the EntityInterface.
   */
  protected function mapFromStorageRecords(array $records, $loadFromRevision = FALSE) {
    $this->entityClass = $this->getEntityTypeClass();

    return parent::mapFromStorageRecords($records, $loadFromRevision);
  }

}
