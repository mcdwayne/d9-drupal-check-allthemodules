<?php

namespace Drupal\entity_pilot_git;

/**
 * Interface EntityOperationsInterface.
 *
 * @package Drupal\entity_pilot_git
 */
interface EntityOperationsInterface {

  /**
   * Check if any entities have been updated since a given timestamp.
   *
   * @param int $from_date
   *   Timestamp to check for content from.
   * @param array $entity_types
   *   The entity types ids to check, if ommitted all entity types
   *   will be checked.
   *
   * @return bool
   *   Whether there is content to update or not.
   */
  public function checkForUpdates($from_date, array $entity_types = []);

  /**
   * Fetches a group of entities of given types from a given date.
   *
   * @param string $entity_type_id
   *   The entity type id to check.
   * @param int $from_date
   *   Optional timestamp to check for content from.
   *
   * @return int[]
   *   An array of entity ids.
   */
  public function getEntitiesFromDate($entity_type_id, $from_date = 0);

}
