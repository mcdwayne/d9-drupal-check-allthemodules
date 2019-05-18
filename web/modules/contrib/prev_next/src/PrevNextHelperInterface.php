<?php

namespace Drupal\prev_next;

/**
 * Interface for PrevNextHelper.
 */
interface PrevNextHelperInterface {

  /**
   * Determine if connection should be refreshed.
   *
   * @return array
   *   Returns the list of Node types.
   */
  public function getBundleNames();

  /**
   * Loads the Prev/next bundle configuration.
   *
   * @param string $bundle_name
   *   Entity Type name.
   *
   * @return \Drupal\Core\Config\ImmutableConfig
   *   A configuration object.
   */
  public function loadBundle($bundle_name);

  /**
   * Callable API function to get the next/prev id of a given entity id.
   *
   * @param int $entity_id
   *   Entity id.
   * @param string $op
   *   The type of operation.
   */
  public function getPrevnextId($entity_id, $op = 'next');

  /**
   * Callable API function to retun the prev id of a given entity id.
   *
   * @param int $entity_id
   *   Entity id.
   *
   * @return int|null
   *   Entity id if found.
   */
  public function getPrevId($entity_id);

  /**
   * Callable API function to retun the next id of a given entity id.
   *
   * @param int $entity_id
   *   Entity id.
   *
   * @return int|null
   *   Entity id if found.
   */
  public function getNextId($entity_id);

}
