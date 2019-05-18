<?php

namespace Drupal\cached_computed_field;

/**
 * Represents an entity that has a field containing expired data.
 */
class ExpiredItem implements ExpiredItemInterface {

  /**
   * The entity type ID.
   *
   * @var string
   */
  protected $entityTypeId;

  /**
   * The entity ID.
   *
   * @var string|int
   */
  protected $entityId;

  /**
   * The field name.
   *
   * @var string
   */
  protected $fieldName;

  /**
   * Constructs a new ExpiredItem.
   *
   * @param string $entityTypeId
   *   The entity type ID.
   * @param string|int $entityId
   *   The entity ID.
   * @param string $fieldName
   *   The field name.
   */
  public function __construct($entityTypeId, $entityId, $fieldName) {
    $this->entityTypeId = $entityTypeId;
    $this->entityId = $entityId;
    $this->fieldName = $fieldName;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeId() {
    return $this->entityTypeId;
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityId() {
    return $this->entityId;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldName() {
    return $this->fieldName;
  }

}
