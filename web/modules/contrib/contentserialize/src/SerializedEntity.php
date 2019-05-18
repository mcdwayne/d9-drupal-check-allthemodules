<?php

namespace Drupal\contentserialize;

/**
 * Wrapper for a serialized entity and its metadata.
 *
 * @package Drupal\contentserialize
 */
class SerializedEntity {

  /**
   * The serialized entity.
   *
   * @var string
   */
  protected $serialized;

  /**
   * The serialization format.
   *
   * @var string
   */
  protected $format;

  /**
   * The entitity's UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * Creates a SerializedEntity.
   *
   * @param string $serialized
   *   The serialized entity.
   * @param string $format
   *   The serialization format.
   * @param string $uuid
   *   The entity UUID.
   * @param string $entity_type_id
   *   The entity type ID.
   */
  public function __construct($serialized, $format, $uuid, $entity_type_id) {
    $this->serialized = $serialized;
    $this->format = $format;
    $this->uuid = $uuid;
    $this->entityTypeId = $entity_type_id;
  }

  /**
   * Get the serialized entity.
   *
   * @return string
   */
  public function getSerialized() {
    return $this->serialized;
  }

  /**
   * Get the serialization format.
   *
   * @return string
   */
  public function getFormat() {
    return $this->format;
  }

  /**
   * Get the entity UUID.
   *
   * @return string
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * Get the entity type ID.
   *
   * @return string
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

}