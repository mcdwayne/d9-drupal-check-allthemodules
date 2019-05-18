<?php

namespace Drupal\radioactivity;

use Drupal\Core\Site\Settings;
use Drupal\Component\Serialization\Json;

/**
 * Defines the incident class.
 */
class Incident {

  /**
   * The incident field name.
   *
   * @var string
   */
  private $fieldName;

  /**
   * The incident entity type.
   *
   * @var string
   */
  private $entityType;

  /**
   * The incident entity id.
   *
   * @var string|int
   */
  private $entityId;

  /**
   * The incident energy.
   *
   * @var int|float
   */
  private $energy;

  /**
   * The incident hash.
   *
   * @var string
   */
  private $hash;

  /**
   * Constructor.
   *
   * @param string $field_name
   *   The field name from the incident.
   * @param string $entity_type
   *   The entity type from the incident.
   * @param string|int $entity_id
   *   The entity id from the incident.
   * @param int|float $energy
   *   The energy from the incident.
   * @param string $hash
   *   The hash from the incident.
   */
  public function __construct($field_name, $entity_type, $entity_id, $energy, $hash = NULL) {
    $this->fieldName  = $field_name;
    $this->entityType = $entity_type;
    $this->entityId   = $entity_id;
    $this->energy     = $energy;
    $this->hash       = $hash;
  }

  /**
   * Test validity of the Incident.
   *
   * @return bool
   *   True if the incident is valid. False if not.
   */
  public function isValid() {
    return strcmp($this->hash, $this->calculateHash()) === 0;
  }

  /**
   * Calculate hash for this incident.
   *
   * @return string
   *   The calculated hash of this incident.
   */
  private function calculateHash() {
    return sha1(implode('##', [
      $this->fieldName,
      $this->entityType,
      $this->entityId,
      $this->energy,
      Settings::getHashSalt(),
    ]));
  }

  /**
   * Convert to JSON format.
   *
   * @return string
   *   Json encoded incident data.
   */
  public function toJson() {
    return Json::encode([
      'fn' => $this->fieldName,
      'et' => $this->entityType,
      'id' => $this->entityId,
      'e' => $this->energy,
      'h' => $this->calculateHash(),
    ]);
  }

  /**
   * Create an Incident from data received in an http request.
   *
   * @param array $data
   *   Associative array of incident data.
   *
   * @return \Drupal\radioactivity\Incident
   *   An Incident object.
   */
  public static function createFromPostData(array $data) {
    $data += [
      'fn' => '',
      'et' => '',
      'id' => '',
      'e' => 0,
      'h' => '',
    ];
    return new Incident($data['fn'], $data['et'], $data['id'], $data['e'], $data['h']);
  }

  /**
   * Create an Incident from field items, an item within it and a formatter.
   *
   * @param object $items
   *   The items containing item.
   * @param object $item
   *   The item in question.
   * @param object $formatter
   *   The formatter in use.
   *
   * @return Incident
   *   The incident object.
   */
  public static function createFromFieldItemsAndFormatter($items, $item, $formatter) {
    return new Incident(
      $items->getName(),
      $item->getEntity()->getEntityTypeId(),
      $item->getEntity()->id(),
      $formatter->getSetting('energy')
    );
  }

  /**
   * Returns the incident field name.
   *
   * @return string
   *   The incident field name.
   */
  public function getFieldName() {
    return $this->fieldName;
  }

  /**
   * Returns the incident entity type.
   *
   * @return string
   *   The incident entity type.
   */
  public function getEntityTypeId() {
    return $this->entityType;
  }

  /**
   * Returns the incident entity id.
   *
   * @return string|int
   *   The incident entity id.
   */
  public function getEntityId() {
    return $this->entityId;
  }

  /**
   * Returns the incident energy.
   *
   * @return int|float
   *   The incident energy.
   */
  public function getEnergy() {
    return $this->energy;
  }

}
