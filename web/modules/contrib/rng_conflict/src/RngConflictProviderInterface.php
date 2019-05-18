<?php

/**
 * @file
 * Contains \Drupal\rng_conflict\RngConflictProviderInterface.
 */

namespace Drupal\rng_conflict;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Database\Query\AlterableInterface;

/**
 * Interface for RNG conflict provider.
 */
interface RngConflictProviderInterface {

  /**
   * Get similar events to an event which are conflicting.
   *
   * @param \Drupal\Core\Entity\EntityInterface $event
   *   An event entity.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]
   *   An array of conflicting events.
   */
  public function getSimilarEvents(EntityInterface $event);

  /**
   * Alter a query to remove registrants which are in similar events.
   *
   * @param \Drupal\Core\Database\Query\AlterableInterface $query
   *   An entity reference query.
   *
   * @return NULL
   */
  public function alterQuery(AlterableInterface &$query);

  /**
   * Get conflict sets for an event type bundle.
   *
   * @param string $entity_type_id
   *   An event type' entity type ID.
   * @param string $bundle
   *   An event type' bundle.
   *
   * @return array
   *   An array of conflict sets.
   */
  public function getSets($entity_type_id, $bundle);

}
