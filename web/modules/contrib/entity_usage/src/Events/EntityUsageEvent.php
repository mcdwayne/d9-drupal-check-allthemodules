<?php

namespace Drupal\entity_usage\Events;

use Symfony\Component\EventDispatcher\Event;

/**
 * Implementation of Entity Usage events.
 */
class EntityUsageEvent extends Event {

  /**
   * The identifier of the target entity.
   *
   * @var string
   */
  protected $targetEntityId;

  /**
   * The type of the target entity.
   *
   * @var string
   */
  protected $targetEntityType;

  /**
   * The identifier of the referencing entity.
   *
   * @var string
   */
  protected $referencingEntityId;

  /**
   * The type of the entity that is referencing.
   *
   * @var string
   */
  protected $referencingEntityType;

  /**
   * The method or way the two entities are being referenced.
   *
   * @var string
   */
  protected $method;

  /**
   * The number of references to add or remove.
   *
   * @var string
   */
  protected $count;

  /**
   * EntityUsageEvents constructor.
   *
   * @param int $t_id
   *   The identifier of the target entity.
   * @param string $t_type
   *   The type of the target entity.
   * @param int $re_id
   *   The identifier of the referencing entity.
   * @param string $re_type
   *   The type of the entity that is referencing.
   * @param string $method
   *   The method or way the two entities are being referenced.
   * @param int $count
   *   The number of references to add or remove.
   */
  public function __construct($t_id = NULL, $t_type = NULL, $re_id = NULL, $re_type = NULL, $method = NULL, $count = NULL) {
    $this->targetEntityId = $t_id;
    $this->targetEntityType = $t_type;
    $this->referencingEntityId = $re_id;
    $this->referencingEntityType = $re_type;
    $this->method = $method;
    $this->count = $count;
  }

  /**
   * Sets the target entity id.
   *
   * @param int $id
   *   The target entity id.
   */
  public function setTargetEntityId($id) {
    $this->targetEntityId = $id;
  }

  /**
   * Sets the target entity type.
   *
   * @param string $type
   *   The target entity type.
   */
  public function setTargetEntityType($type) {
    $this->targetEntityType = $type;
  }

  /**
   * Sets the referencing entity id.
   *
   * @param int $id
   *   The referencing entity id.
   */
  public function setReferencingEntityId($id) {
    $this->referencingEntityId = $id;
  }

  /**
   * Sets the referencing entity type.
   *
   * @param string $type
   *   The referencing entity type.
   */
  public function setReferencingEntityType($type) {
    $this->referencingEntityType = $type;
  }

  /**
   * Sets the referencing method.
   *
   * @param string $method
   *   The referencing method.
   */
  public function setReferencingMethod($method) {
    $this->method = $method;
  }

  /**
   * Sets the count.
   *
   * @param int $count
   *   The number od references to add or remove.
   */
  public function setCount($count) {
    $this->count = $count;
  }

  /**
   * Gets the target entity id.
   *
   * @return null|string
   *   The target entity id or NULL.
   */
  public function getTargetEntityId() {
    return $this->targetEntityId;
  }

  /**
   * Gets the target entity type.
   *
   * @return null|string
   *   The target entity type or NULL.
   */
  public function getTargetEntityType() {
    return $this->targetEntityType;
  }

  /**
   * Gets the referencing entity id.
   *
   * @return int|null
   *   The referencing entity id or NULL.
   */
  public function getReferencingEntityId() {
    return $this->referencingEntityId;
  }

  /**
   * Gets the referencing entity type.
   *
   * @return null|string
   *   The referencing entity type or NULL.
   */
  public function getReferencingEntityType() {
    return $this->referencingEntityType;
  }

  /**
   * Gets the referencing method.
   *
   * @return null|string
   *   The referencing method or NULL.
   */
  public function getReferencingMethod() {
    return $this->method;
  }

  /**
   * Gets the count.
   *
   * @return int|null
   *   The number of references to add or remove or NULL.
   */
  public function getCount() {
    return $this->count;
  }

}
