<?php

namespace Drupal\entity_delete_op;

/**
 * Provides an interface for defining deletion managers.
 */
interface DeleteManagerInterface {

  /**
   * Marks the entity as deleted but does not perform complete removal.
   *
   * @param \Drupal\entity_delete_op\EntityDeletableInterface $entity
   *   The entity to be deleted.
   */
  public function delete(EntityDeletableInterface $entity);

  /**
   * Restores the entity from being marked as deleted.
   *
   * @param \Drupal\entity_delete_op\EntityDeletableInterface $entity
   *   The entity to be restored.
   */
  public function restore(EntityDeletableInterface $entity);

  /**
   * Purges the entity from persistent storage (e.g. database).
   *
   * @param \Drupal\entity_delete_op\EntityDeletableInterface $entity
   *   The entity to be purged.
   */
  public function purge(EntityDeletableInterface $entity);

}
