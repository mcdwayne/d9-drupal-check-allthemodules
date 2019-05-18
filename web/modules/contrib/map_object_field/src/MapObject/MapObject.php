<?php
namespace Drupal\map_object_field\MapObject;

use JsonSerializable;

/**
 * Map Object.
 */
class MapObject implements JsonSerializable {
  protected $id = '';
  protected $entityType = '';
  protected $entityId = '';
  protected $entityRevisionId = '';
  protected $entityFieldDelta = 0;
  protected $objectType = '';
  protected $objectCoordinates = [];
  protected $extraParams = [];
  protected $map = [
    'map_object_id' => 'id',
    'type' => 'objectType',
    'entity_type' => 'entityType',
    'entity_id' => 'entityId',
    'entity_revision_id' => 'entityRevisionId',
    'entity_field_delta' => 'entityFieldDelta',
    'coordinates' => 'objectCoordinates',
    'extraParams' => 'extraParams',
  ];

  /**
   * Constructor.
   */
  public function __construct($data) {
    foreach ($data as $key => $val) {
      if (array_key_exists($key, $this->map)) {
        $set_method = 'set' . ucfirst($this->map[$key]);
        $this->$set_method($val);
      }
    }
  }

  /**
   * Getter for id.
   */
  public function getId() {
    return $this->id;
  }

  /**
   * Setter for id.
   */
  public function setId($id) {
    $this->id = $id;
  }

  /**
   * Getter for entityType.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * Setter for entityType.
   */
  public function setEntityType($entity_type) {
    $this->entityType = $entity_type;
  }

  /**
   * Getter for entityId.
   */
  public function getEntityId() {
    return $this->entityId;
  }

  /**
   * Setter for entityId.
   */
  public function setEntityId($entity_id) {
    $this->entityId = $entity_id;
  }

  /**
   * Getter for entityRevisionId.
   */
  public function getEntityRevisionId() {
    return $this->entityRevisionId;
  }

  /**
   * Setter for entityRevisionId.
   */
  public function setEntityRevisionId($entity_revision_id) {
    $this->entityRevisionId = $entity_revision_id;
  }

  /**
   * Getter for entityFieldDelta.
   */
  public function getEntityFieldDelta() {
    return $this->entityFieldDelta;
  }

  /**
   * Setter fot entityFieldDelta.
   */
  public function setEntityFieldDelta($entity_field_delta) {
    $this->entityFieldDelta = $entity_field_delta;
  }

  /**
   * Getter for objectType.
   */
  public function getObjectType() {
    return $this->objectType;
  }

  /**
   * Setter for objectType.
   */
  public function setObjectType($object_type) {
    $this->objectType = $object_type;
  }

  /**
   * Getter for objectCoordinates.
   */
  public function getObjectCoordinates() {
    return $this->objectCoordinates;
  }

  /**
   * Setter for objectCoordinates.
   */
  public function setObjectCoordinates($object_coordiantes) {
    $this->objectCoordinates = $object_coordiantes;
  }

  /**
   * Getter for extraParam.
   */
  public function getExtraParam($key) {
    if (array_key_exists($key, $this->extraParams)) {
      return $this->extraParams[$key];
    }
    return NULL;
  }

  /**
   * Setter for extraParam.
   */
  public function setExtraParam($key, $val) {
    $this->extraParams[$key] = $val;
  }

  /**
   * Getter for extraParams.
   */
  public function getExtraParams() {
    return $this->extraParams;
  }

  /**
   * Setter for extraParams.
   */
  public function setExtraParams($val) {
    $this->extraParams = $val;
  }

  /**
   * JsonSerializable intrface implementation.
   */
  public function jsonSerialize() {
    $inverted_map = array_flip($this->map);
    $result = [];
    foreach (get_object_vars($this) as $property_name => $property_value) {
      if (isset($inverted_map[$property_name])) {
        $result[$inverted_map[$property_name]] = $property_value;
      }
    }
    return $result;
  }

}
