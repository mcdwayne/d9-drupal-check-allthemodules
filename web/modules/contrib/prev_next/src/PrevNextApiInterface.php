<?php

namespace Drupal\prev_next;

/**
 * Interface for PrevNextApi.
 */
interface PrevNextApiInterface {

  /**
   * Create the prev_next records.
   *
   * @param int $entity_id
   *   Entity id.
   * @param string $bundle_name
   *   Entity type.
   */
  public function add($entity_id, $bundle_name);

  /**
   * Update the prev_next records.
   *
   * @param int $entity_id
   *   Entity id.
   * @param string $bundle_name
   *   Entity type.
   */
  public function update($entity_id, $bundle_name);

  /**
   * Remove the prev_next records.
   *
   * @param int $entity_id
   *   Entity id.
   * @param string $bundle_name
   *   Entity type.
   */
  public function remove($entity_id, $bundle_name);

  /**
   * Helper function to return a SQL clause for bundles to be indexed.
   *
   * @param string $bundle_name
   *   The indexing criteria for the type of entity to query for.
   * @param object $bundle
   *   Prev/Next bundle configuration.
   *
   * @return string
   *   Returns the sql string.
   */
  public function bundlesSql($bundle_name, $bundle);

  /**
   * Helper function to update other entities pointing to a particular entity.
   *
   * @param int $entity_id
   *   Entity id.
   * @param string $bundle_name
   *   Entity type.
   */
  public function modifyPointingEntities($entity_id, $bundle_name);

}
