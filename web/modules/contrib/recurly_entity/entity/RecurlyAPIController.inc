<?php

/**
 * @class
 * Contains all common methods for RecurlyAPI controllers. For example, this
 * contains the logic which handles EntityCache integration common to all
 * Recurly entities.
 */
abstract class RecurlyAPIController extends EntityAPIController implements RecurlyRemoteEntity {

  /**
   * Override the load() method to fetch our objects from Recurly first and then
   * store them in the entity cache. We can then call the parent method to
   * query our base table for any cached entities.
   *
   * Note that if conditions() are specified, that the query will only match
   * entities that have been previously cached.
   */
  public function load($ids = [], $conditions = []) {
    $entities = [];
    $original_ids = $ids;

    if (!empty($this->entityInfo['entity cache']) && $ids && !$conditions) {
      // We need to initalize the Recurly client so we can unserialize()
      // Recurly objects stored in the cache.
      recurly_client_initialize();

      $cached_entities = EntityCacheControllerHelper::entityCacheGet($this, $ids, $conditions);
      // If any entities were loaded, remove them from the ids still to load.
      $ids = array_diff($ids, array_keys($cached_entities));
      $entities += $cached_entities;

      // Add loaded entities to the static cache if we are not loading a
      // revision.
      if ($this->cache && !empty($cached_entities)) {
        $this->cacheSet($cached_entities);
      }
    }
    if (!($this->cacheComplete && $ids === FALSE && !$conditions) && ($ids === FALSE || $ids)) {
      // Note that we attach fields in loadFromRecurly, right before we store
      // the plan in the entity cache.
      $entities = $this->loadFromRecurlyMultiple($this->recurlyCodeMultiple($ids));
    }

    // This isn't as bad for performance as it seems as we statically cache
    // fetched entities from above.
    return parent::load($original_ids, $conditions);
  }

  /**
   * Store entities in the entity cache.
   *
   * @param array $entities
   *   An array of RecurlySubscriptionPlan entities.
   */
  public function entityCacheSet(array $entities) {
    if (!empty($this->entityInfo['entity cache']) && !empty($entities)) {
      EntityCacheControllerHelper::entityCacheSet($this, $entities);
    }
  }

  /**
   * Load an entity from Recurly using it's unique identifier.
   *
   * @param array $ids
   *   An array of Recurly IDs to load, such as the plan_code for a Subscription
   *   Plan or the account_code for an Account.
   */
  public function loadFromRecurlyMultiple(array $ids) {
    $entities = [];
    foreach ($ids as $id) {
      $entities[] = $this->loadFromRecurly($id);
    }

    return $entities;
  }

  /**
   * Map a Recurly entity UUID to it's entity ID.
   *
   * @param string $uuid
   *   The UUID of the entity.
   *
   * @return int
   *   The entity ID, or FALSE if one is not found.
   */
  public function entityId($uuid) {
    $result = $this->entityIdMultiple([$uuid]);
    return reset($result);
  }

  /**
   * Map multiple Recurly entity UUIDs to their entity ID.
   *
   * @param array $uuids
   *   An array of Recurly entity UUIDs.
   *
   * @return array
   *   An array of entity IDs, keyed by their UUID.
   */
  public function entityIdMultiple(array $uuids) {
    $ids = entity_get_id_by_uuid($this->entityType, $uuids);
    // TODO: If a UUID isn't found, pull it down.
    return $ids;
  }

  /**
   * Map an Entity ID to a Recurly UUID.
   *
   * @param int $entity_id
   *   The entity ID of the Recurly entity.
   *
   * @return string
   *   The unique Recurly UUID, or FALSE if one is not found.
   */
  public function recurlyCode($entity_id) {
    $result = $this->recurlyCodeMultiple([$entity_id]);
    return reset($result);
  }

  /**
   * Map multiple entity IDs to their Recurly UUID.
   *
   * @param array $entity_ids
   *   The array of entity IDs to map.
   *
   * @return array
   *   An array of Recurly UUIDs.
   */
  public function recurlyCodeMultiple(array $entity_ids) {
    return entity_get_uuid_by_id($this->entityType, $entity_ids);
  }

  /**
   * Override save() to check for a valid local owner.
   *
   * @param RecurlyEntity $entity
   *   The recurly entity being saved.
   * @param DatabaseTransaction $transaction
   *   Optional parameter of the transaction associated with this save.
   * @param bool $validate_owner
   *   Optional parameter to validate the owner of the entity before saving.
   *   Defaults to TRUE.
   *
   * @return bool|int
   *   The status of the save operation.
   */
  public function save($entity, DatabaseTransaction $transaction = NULL, $validate_owner = TRUE) {
    if ($validate_owner) {
      $this->validateOwner($entity);
    }

    return parent::save($entity, $transaction);
  }

  /**
   * Validate that a Recurly entity has a local Drupal owner.
   *
   * @param RecurlyEntity $entity
   *   The entity to validate has both an entity_type and entity_id property.
   *
   * @throws RecurlyMissingOwnerException
   */
  protected function validateOwner(RecurlyEntity $entity) {
    if (!isset($entity->entity_type) || !isset($entity->entity_id)) {
      throw new RecurlyMissingOwnerException('This entity must have an associated owner entity to be saved.');
    }
  }

  /**
   * Load an entity from Recurly using it's unique identifier.
   *
   * @param string $id
   *   The ID of the resource to load.
   *
   * @return RecurlyEntity
   *   The entity that was loaded.
   */
  public function loadFromRecurly($id) {
    recurly_client_initialize();

    $resource = $this->resourceClass();
    $response = $resource->get($id);
    return $this->loadFromResource($response);
  }

  /**
   * Load a Recurly Entity from a Recurly_Resource object.
   *
   * This method is best used when another request has already loaded an object
   * from Recurly. Otherwise, call loadFromRecurly() with an ID to load.
   *
   * @param Recurly_Resource $resource
   *   The Recurly object to load an entity from.
   *
   * @return RecurlyEntity
   *   The entity that was loaded.
   */
  public function loadFromResource(Recurly_Resource $resource) {
    // We can't use create() as it only works for new entities.
    if (isset($this->entityInfo['entity class']) && $class = $this->entityInfo['entity class']) {
      $entity = new $class($resource->getValues());
    }

    // Update our local entity cache, but only if we have saved this entity
    // locally.
    if (isset($entity->id)) {
      // Attach our fields.
      $wrapper = [$entity->id => $entity];
      $this->attachLoad($wrapper);
      $this->entityCacheSet([$entity]);
    }

    return $entity;
  }
}

// Generic exception for when a entity has no valid owner.
class RecurlyMissingOwnerException extends Exception {}
