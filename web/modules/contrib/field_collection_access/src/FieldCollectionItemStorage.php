<?php

namespace Drupal\field_collection_access;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Entity\EntityInterface;

/**
 * Entity Stroage for Field Collection Items.
 *
 * Overrides default class to add hook when entities are saved.
 */
class FieldCollectionItemStorage extends SqlContentEntityStorage {

  /**
   * Override save().
   *
   * After saving we should process the field collection item and update grants.
   */
  public function save(EntityInterface $entity) {
    $v = parent::save($entity);
    $grantStorage = \Drupal::service('field_collection_access.grant_storage');
    $grants = $grantStorage->getRecordsFor($entity);
    $grantStorage->saveRecords($entity, $grants);
    return $v;
  }

}
