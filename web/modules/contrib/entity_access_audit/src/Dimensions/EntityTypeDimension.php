<?php

namespace Drupal\entity_access_audit\Dimensions;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\entity_access_audit\AccessDimensionInterface;

/**
 * Dimension for entity types.
 */
class EntityTypeDimension implements AccessDimensionInterface {

  /**
   * The entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface
   */
  protected $entityType;

  /**
   * EntityTypeDimension constructor.
   */
  public function __construct(EntityTypeInterface $entityType) {
    $this->entityType = $entityType;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Entity type');
  }

  /**
   * {@inheritdoc}
   */
  public function getDimensionValue() {
    return $this->entityType->getLabel();
  }

  /**
   * The entity type.
   *
   * @return EntityTypeInterface
   *   The entity type.
   */
  public function getEntityType() {
    return $this->entityType;
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->entityType->id();
  }

}
