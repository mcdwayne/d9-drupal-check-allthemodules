<?php

namespace Drupal\entity_type_class\Storage;

use Drupal\user\UserStorage as CoreUserStorage;

/**
 * Class UserStorage.
 *
 * Changes the User entity class to a custom class provided in a
 * EntityStorage plugin.
 *
 * @package Drupal\entity_type_class\Storage
 */
abstract class UserStorage extends CoreUserStorage {

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
