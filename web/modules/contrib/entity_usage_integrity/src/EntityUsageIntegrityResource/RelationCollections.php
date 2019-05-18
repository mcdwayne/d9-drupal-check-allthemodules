<?php

namespace Drupal\entity_usage_integrity\EntityUsageIntegrityResource;

use Exception;

/**
 * Marager for relation collections.
 *
 * We can have relation collections storing valid, invalid or broken relations.
 */
class RelationCollections {

  /**
   * Storage for valid usage integrity relations.
   *
   * @var \Drupal\entity_usage_integrity\EntityUsageIntegrityResource\RelationStatusCollection
   */
  protected $validRelations;

  /**
   * Storage for invalid usage integrity relations.
   *
   * @var \Drupal\entity_usage_integrity\EntityUsageIntegrityResource\RelationStatusCollection
   */
  protected $invalidRelations;

  /**
   * Storage for broken usage integrity relations.
   *
   * @var \Drupal\entity_usage_integrity\EntityUsageIntegrityResource\RelationStatusCollection
   */
  protected $brokenRelations;

  /**
   * Create RelationCollections object.
   */
  public function __construct() {
    $this->validRelations = new RelationStatusCollection();
    $this->invalidRelations = new RelationStatusCollection();
    $this->brokenRelations = new RelationStatusCollection();
  }

  /**
   * Get collection of given relation status.
   *
   * @param string $relation_status
   *   A 'valid' if we would like to get number of valid relations,
   *   'invalid' if we would like to get number of invalid relations,
   *   'broken' if we would like to get number of broken relations.
   *
   *   Broken relation means that related_entity doesn't exists.
   *
   * @return \Drupal\entity_usage_integrity\EntityUsageIntegrityResource\RelationStatusCollection
   *   Collection of relations with given relation status.
   */
  public function getRelationCollectionWithStatus($relation_status) {
    return $this->getRelationCollection($relation_status);
  }

  /**
   * Check if collection of given relation status contains any elements.
   *
   * @param string $relation_status
   *   A 'valid' if we would like to get number of valid relations,
   *   'invalid' if we would like to get number of invalid relations,
   *   'broken' if we would like to get number of broken relations.
   *
   *   Broken relation means that related_entity doesn't exists.
   *
   * @return bool
   *   TRUE if collection of relations for given status contains any elements,
   *   FALSE otherwise.
   */
  public function hasRelationsWithStatus($relation_status) {
    return $this->getRelationCollection($relation_status)->count() > 0;
  }

  /**
   * Get collection storing relations of given status.
   *
   * @param string $relation_status
   *   A 'valid' if we would like to get number of valid relations,
   *   'invalid' if we would like to get number of invalid relations,
   *   'broken' if we would like to get number of broken relations.
   *
   * @return \Drupal\entity_usage_integrity\EntityUsageIntegrityResource\RelationStatusCollection
   *   Relation collection of given type.
   *
   * @throws \Exception
   *   If relation collection not found.
   */
  protected function getRelationCollection($relation_status) {
    switch ($relation_status) {
      case 'valid':
        return $this->validRelations;

      case 'invalid':
        return $this->invalidRelations;

      case 'broken':
        return $this->brokenRelations;

      default:
        throw new Exception('Entity usage relation status not defined.');
    }
  }

}
