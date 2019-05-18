<?php

namespace Drupal\entity_pilot\Utility;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\entity_pilot\FlightInterface;

/**
 * Defines an interface for a stub wrapper for an entity.
 */
interface FlightStubInterface {

  /**
   * Returns the entity wrapped by the stub.
   *
   * @throws \LogicException
   *
   * @return \Drupal\entity_pilot\FlightInterface
   *   The entity wrapped by the stub
   */
  public function getEntity();

  /**
   * Factory method to create a new \Drupal\entity_pilot\Utility\FlightStub.
   *
   * @param int $revision_id
   *   The revision ID of the departure to be wrapped.
   *
   * @return static
   *   New instance
   */
  public static function create($revision_id);

  /**
   * Sets the storage controller of the stub.
   *
   * Must be called before calling getEntity().
   *
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage handler.
   *
   * @return self
   *   The instance on which the method was called.
   */
  public function setStorage(EntityStorageInterface $storage);

  /**
   * Sets the entity wrapped by the stub.
   *
   * @param \Drupal\entity_pilot\FlightInterface $entity
   *   The entity to wrap.
   *
   * @return self
   *   The stub instance on which the method was called.
   */
  public function setEntity(FlightInterface $entity);

  /**
   * Gets the revision ID of the stub.
   *
   * @return int
   *   The revision ID.
   */
  public function getRevisionId();

  /**
   * Sets the revision ID of the stub.
   *
   * @param int $revision_id
   *   The revision ID to set for the stub.
   *
   * @return self
   *   The instance on which the method was called.
   */
  public function setRevisionId($revision_id);

}
