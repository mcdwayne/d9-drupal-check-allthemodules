<?php

namespace Drupal\developer_suite\Storage;

use Drupal\taxonomy\TermStorage as CoreTermStorage;

/**
 * Class TermStorage.
 *
 * Changes the Term entity class to a custom class provided in a
 * EntityTypeClass plugin.
 *
 * @package Drupal\developer_suite\Storage
 */
abstract class TermStorage extends CoreTermStorage {

  /**
   * {@inheritdoc}
   */
  protected function doCreate(array $values) {
    $this->entityClass = $this->getEntityTypeClass($values['type']);

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
