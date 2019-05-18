<?php

namespace Drupal\entity_pilot\Utility;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\entity_pilot\FlightInterface;

/**
 * Defines a stub wrapper for an entity.
 */
class FlightStub implements FlightStubInterface {

  /**
   * Revision ID for entity.
   *
   * @var int
   */
  protected $revisionId;

  /**
   * Departure or arrival entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $storage;

  /**
   * Statically cached, loaded departure or arrival entity.
   *
   * @var \Drupal\entity_pilot\FlightInterface
   */
  protected $entity = NULL;

  /**
   * {@inheritdoc}
   */
  public static function create($revision_id) {
    return new static($revision_id);
  }

  /**
   * Constructs a new \Drupal\entity_pilot\Utility\FlightStub object.
   *
   * @param int $revision_id
   *   The revision ID this stub wraps.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   (optional) The storage service.
   * @param \Drupal\entity_pilot\FlightInterface $entity
   *   (optional) Entity to wrap.
   */
  public function __construct($revision_id, EntityStorageInterface $storage = NULL, FlightInterface $entity = NULL) {
    $this->entity = $entity;
    $this->storage = $storage;
    $this->revisionId = $revision_id;
  }

  /**
   * {@inheritdoc}
   */
  public function __sleep() {
    unset($this->entity, $this->storage);
    return array_keys(get_object_vars($this));
  }

  /**
   * Sets the entity wrapped by the stub.
   *
   * @param \Drupal\entity_pilot\FlightInterface $entity
   *   The entity to wrap.
   *
   * @return self
   *   The stub instance on which the method was called.
   */
  public function setEntity(FlightInterface $entity) {
    $this->entity = $entity;
    $this->revisionId = $entity->getRevisionId();
    return $this;
  }

  /**
   * Returns the entity wrapped by the stub.
   *
   * @throws \LogicException
   *
   * @return \Drupal\entity_pilot\FlightInterface
   *   The entity wrapped by the stub
   */
  public function getEntity() {
    if (!$this->entity) {
      if (!$this->storage) {
        throw new \LogicException('Attempt to load entity without initializing storage.');
      }
      $this->entity = $this->storage->loadRevision($this->revisionId);
    }
    return $this->entity;
  }

  /**
   * Sets the revision ID of the stub.
   *
   * @param int $revision_id
   *   The revision ID to set for the stub.
   *
   * @return self
   *   The instance on which the method was called.
   */
  public function setRevisionId($revision_id) {
    $this->revisionId = $revision_id;
    return $this;
  }

  /**
   * Gets the revision ID of the stub.
   *
   * @return int
   *   The revision ID.
   */
  public function getRevisionId() {
    return $this->revisionId;
  }

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
  public function setStorage(EntityStorageInterface $storage) {
    $this->storage = $storage;
    return $this;
  }

}
