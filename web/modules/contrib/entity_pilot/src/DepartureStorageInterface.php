<?php

namespace Drupal\entity_pilot;

use Drupal\Core\Entity\Sql\SqlEntityStorageInterface;

/**
 * Defines an interface for storage of departure entities.
 */
interface DepartureStorageInterface extends SqlEntityStorageInterface {

  /**
   * Returns dependant entities for a given Departure entity.
   *
   * @param \Drupal\entity_pilot\DepartureInterface $entity
   *   The entity for which the dependencies are being calculated.
   *
   * @return \Drupal\Core\Entity\EntityInterface[]|array
   *   Array of dependant entities.
   */
  public function getDependencies(DepartureInterface $entity);

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
   * @param \Drupal\entity_pilot\DepartureInterface $entity
   *   The entity to queue.
   *
   * @return self
   *   The instance the method was called on.
   */
  public function queue(DepartureInterface $entity);

}
