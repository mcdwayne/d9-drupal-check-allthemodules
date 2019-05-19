<?php

namespace Drupal\staged_content\DataProxy;

/**
 * Helper class to containing meta data about a storage item in the file system.
 *
 * Makes it simpler to keep track of the various files etc. By making a small
 * abstraction instead of passing assoc arrays everywhere.
 *
 * Every item represents a single file in the storage set.
 */
class JsonDataProxy implements DataProxyInterface {

  /**
   * File name containing all the data.
   *
   * @var string
   *   The file name for the item.
   */
  protected $fileName;

  /**
   * Uuid for the item in the file.
   *
   * @var string
   *   The uuid for the item.
   */
  protected $uuid;

  /**
   * The entity type contained in the file.
   *
   * @var string
   *   The entity type for the item.
   */
  protected $entityType;

  /**
   * Extra data marker.
   *
   * @var string
   *   Additional marker connected to the content set, such as "acc" or "dev"
   */
  protected $marker;

  /**
   * JsonDataProxy constructor.
   *
   * @param string $fileName
   *   File with the json data.
   * @param string $uuid
   *   Uuid for the item connected to the file.
   * @param string $entityType
   *   Entity type provided by the file.
   * @param string $marker
   *   Marker with the environment the file is valid for.
   */
  public function __construct(string $fileName, string $uuid, string $entityType, string $marker) {
    $this->setEntityType($entityType);
    $this->setFileName($fileName);
    $this->setMarker($marker);
    $this->setUuid($uuid);
  }

  /**
   * Get the entity type.
   *
   * @return string
   *   The entity type.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Get the file name.
   *
   * @return string
   *   The filename.
   */
  public function getFileName() {
    return $this->fileName;
  }

  /**
   * Get the actual stored data.
   *
   * @return array
   *   The data in storage.
   */
  public function getData() {
    return json_decode($this->getRawData(), TRUE);
  }

  /**
   * Get the actual stored data.
   *
   * @return string
   *   The data in storage.
   */
  public function getRawData() {
    return file_get_contents($this->getFileName());
  }

  /**
   * Get the marker for this item.
   *
   * @return string
   *   The marker for this item.
   */
  public function getMarker() {
    return $this->marker;
  }

  /**
   * Get the uuid.
   *
   * @return string
   *   The uuid for this item.
   */
  public function getUuid() {
    return $this->uuid;
  }

  /**
   * Set the entity type.
   *
   * @param string $entityType
   *   Set the entity type for this entity type.
   */
  public function setEntityType(string $entityType) {
    $this->entityType = $entityType;
  }

  /**
   * Set the file name.
   *
   * @param string $fileName
   *   Set the filename.
   */
  public function setFileName(string $fileName) {
    $this->fileName = $fileName;
  }

  /**
   * Set the marker name.
   *
   * @param string $marker
   *   Set the marker name.
   */
  public function setMarker(string $marker) {
    $this->marker = $marker;
  }

  /**
   * Set the uuid.
   *
   * @param string $uuid
   *   The uuid to set.
   */
  public function setUuid(string $uuid) {
    $this->uuid = $uuid;
  }

}
