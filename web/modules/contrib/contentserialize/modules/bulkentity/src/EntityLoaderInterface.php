<?php

namespace Drupal\bulkentity;

use Drupal\Core\Entity\Query\QueryInterface;

/**
 * Provides an interface for loading entities in bulk.
 *
 * Note this will bust the persistent entity cache as well as static.
 * @see https://www.drupal.org/project/drupal/issues/2577417#comment-10658038
 * @see https://www.drupal.org/project/drupal/issues/1596472
 */
interface EntityLoaderInterface {

  /**
   * Loads entities by ID in batches.
   *
   * @param int $batch_size
   *   The number of loaded entities to keep in memory at one time.
   * @param array $ids
   *   An array of entity IDs.
   * @param string $entity_type_id
   *   The entity type ID, eg. 'node'.
   *
   * @return \Generator
   *   A generator yielding the loaded entities.
   */
  public function byIds($batch_size, array $ids, $entity_type_id);

  /**
   * Loads entities by entity query in batches.
   *
   * @param int $batch_size
   *   The number of loaded entities to keep in memory at one time.
   * @param \Drupal\Core\Entity\Query\QueryInterface $query
   *   The entity query for which entities to load.
   *
   * @return \Generator
   *   A generator yielding the loaded entities.
   */
  public function byQuery($batch_size, QueryInterface $query);

  /**
   * Loads all entities of a type
   *
   * @param int $batch_size
   *   The number of loaded entities to keep in memory at one time.
   * @param string $entity_type_id
   *   The entity type ID, eg. 'node'.
   * @param string[] $bundles
   *   (optional) An array of bundles to filter by; by default all bundles will
   *   be loaded.
   *
   * @return \Generator
   *   A generator yielding the loaded entities.
   */
  public function byEntityType($batch_size, $entity_type_id, $bundles = []);

}