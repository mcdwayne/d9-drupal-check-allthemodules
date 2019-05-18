<?php

namespace Drupal\entity_pilot;

use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

/**
 * Defines an interface for storage of arrival entities.
 */
interface ArrivalStorageInterface extends SqlEntityStorageInterface {

  /**
   * Returns a keyed array of allowed status values.
   *
   * @return array
   *   Array of status values keyed by id.
   */
  public function getAllowedStates();

  /**
   * Queues the entity for sending.
   *
   * @param \Drupal\entity_pilot\ArrivalInterface $entity
   *   The entity to queue.
   *
   * @return self
   *   The instance the method was called on.
   */
  public function queue(ArrivalInterface $entity);

  /**
   * Resets the cache and loads the entity.
   *
   * @param int $id
   *   Arrival ID.
   *
   * @return \Drupal\entity_pilot\ArrivalInterface|null
   *   Loaded arrival or NULL if not exists.
   */
  public function resetCacheAndLoad($id);

}
