<?php

namespace Drupal\entity_delete_op;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Defines a deletion manager.
 */
class DeleteManager implements DeleteManagerInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Creates a new instance of DeleteManager.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function delete(EntityDeletableInterface $entity) {
    if ($entity->isDeleted()) {
      return;
    }

    $entity_type = $entity->getEntityType();
    if (!empty($entity_type) && $entity_type->isRevisionable()) {
      $entity->setNewRevision(TRUE);
    }

    $entity->setIsDeleted(TRUE)
      ->save();

    if ($entity instanceof ContentEntityInterface) {
      Cache::invalidateTags($entity->getCacheTagsToInvalidate());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function restore(EntityDeletableInterface $entity) {
    if (!$entity->isDeleted()) {
      return;
    }

    $entity_type = $entity->getEntityType();
    if (!empty($entity_type) && $entity_type->isRevisionable()) {
      $entity->setNewRevision(TRUE);
    }

    $entity->setIsDeleted(FALSE)
      ->save();

    if ($entity instanceof ContentEntityInterface) {
      Cache::invalidateTags($entity->getCacheTagsToInvalidate());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function purge(EntityDeletableInterface $entity) {
    $this->entityTypeManager->getStorage($entity->getEntityType()->id())
      ->delete([$entity]);

    if ($entity instanceof ContentEntityInterface) {
      Cache::invalidateTags($entity->getCacheTagsToInvalidate());
    }
  }

}
