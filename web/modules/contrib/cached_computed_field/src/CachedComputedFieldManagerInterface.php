<?php

namespace Drupal\cached_computed_field;

/**
 * Interface for services that manage cached computed fields.
 */
interface CachedComputedFieldManagerInterface {

  /**
   * Returns an array containing metadata of expired field items.
   *
   * @return array
   *   An indexed array containing information about expired field items, sorted
   *   by expiration date, each value an associative array with the following
   *   keys:
   *   - entity_type: The entity type ID of the entity that contains the expired
   *     field.
   *   - entity_id: The ID of the entity that contains the expired field.
   *   - field_name: The name of the expired field.
   *   - expire: The UNIX time stamp indicating when this field has expired.
   */
  public function getExpiredItems();

  /**
   * Returns a lightweight map of cached computed fields across bundles.
   *
   * @return array
   *   An array keyed by entity type. Each value is an array which keys are
   *   field names and value is an array with two entries:
   *   - type: The field type.
   *   - bundles: An associative array of the bundles in which the field
   *     appears, where the keys and values are both the bundle's machine name.
   */
  public function getFieldMap();

  /**
   * Returns a list of field types provided by the Cached Computed Field module.
   *
   * @return array
   *   The list of field types.
   */
  public function getFieldTypes();

  /**
   * Returns the queue that holds the fields to process.
   *
   * @return \Drupal\Core\Queue\QueueInterface
   *   The queue object.
   */
  public function getQueue();

  /**
   * Populates the queue with expired items.
   */
  public function populateQueue();

  /**
   * Processes the queue.
   */
  public function processQueue();

}
