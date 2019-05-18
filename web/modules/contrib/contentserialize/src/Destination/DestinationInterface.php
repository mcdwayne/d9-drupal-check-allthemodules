<?php

namespace Drupal\contentserialize\Destination;

/**
 * Provides an interface defining a content export destination.
 */
interface DestinationInterface {

  /**
   * Stores a single serialized entity.
   *
   * @param \Drupal\contentserialize\SerializedEntity $serialized
   *   The serialized entity.
   *
   * @throws \Symfony\Component\Serializer\Exception\RuntimeException
   *   If there's a write error.
   */
  public function save($serialized);

  /**
   * Stores multiple serialized entities.
   *
   * @param \Drupal\contentserialize\SerializedEntity[]|\Traversable $serialized
   *   An array/iterator/generator of serialized entities
   *
   * @throws \Symfony\Component\Serializer\Exception\RuntimeException
   *   If there's a write error.
   */
  public function saveMultiple($serialized);

}