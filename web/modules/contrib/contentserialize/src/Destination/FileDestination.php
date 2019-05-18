<?php

namespace Drupal\contentserialize\Destination;

use Symfony\Component\Serializer\Exception\RuntimeException;

/**
 * Saves serialized entities and metadata on the filesystem.
 */
class FileDestination implements DestinationInterface {

  /**
   * The path of the folder to save to.
   *
   * @var string
   */
  protected $path;

  /**
   * Creates a new FileDestination.
   *
   * @param string $path
   *   The path of the folder to save to.
   */
  public function __construct($path) {
    $this->path = $path;
  }

  /**
   * Stores a single serialized entity.
   *
   * @param \Drupal\contentserialize\SerializedEntity $serialized
   *   The serialized entity.
   *
   * @throws \Symfony\Component\Serializer\Exception\RuntimeException
   *   If there's a write error.
   */
  public function save($serialized) {
    $uuid = $serialized->getUuid();
    $entity_type_id = $serialized->getEntityTypeId();
    $format = $serialized->getFormat();
    $file_name = "$uuid.$entity_type_id.$format";
    if (file_put_contents("$this->path/$file_name", $serialized->getSerialized()) === FALSE) {
      throw new RuntimeException("Couldn't write to file $this->path/$file_name");
    }
  }

  /**
   * Stores multiple serialized entities.
   *
   * @param \Drupal\contentserialize\SerializedEntity[]|\Traversable $serialized
   *   An array/iterator/generator of serialized entities
   *
   * @throws \Symfony\Component\Serializer\Exception\RuntimeException
   *   If there's a write error.
   */
  public function saveMultiple($serialized) {
    foreach ($serialized as $serialized_entity) {
      $this->save($serialized_entity);
    }
  }

}

