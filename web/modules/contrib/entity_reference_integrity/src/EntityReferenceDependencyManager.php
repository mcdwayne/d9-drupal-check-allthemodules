<?php

namespace Drupal\entity_reference_integrity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Calculate entity dependencies based on entity reference fields.
 *
 * @deprecated Use the entity handler instead.
 */
class EntityReferenceDependencyManager implements EntityReferenceDependencyManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Create an EntityReferenceDependencyManager.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public function hasDependents(EntityInterface $entity) {
    return $this->entityTypeManager
      ->getHandler($entity->getEntityTypeId(), 'entity_reference_integrity')
      ->hasDependents($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependentEntityIds(EntityInterface $entity) {
    return $this->entityTypeManager
      ->getHandler($entity->getEntityTypeId(), 'entity_reference_integrity')
      ->getDependentEntityIds($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDependentEntities(EntityInterface $entity) {
    return $this->entityTypeManager
      ->getHandler($entity->getEntityTypeId(), 'entity_reference_integrity')
      ->getDependentEntities($entity);
  }

}
