<?php

namespace Drupal\trash;

use Drupal\Core\Entity\ContentEntityInterface;

interface TrashManagerInterface {

  /**
   * Moves an entity to trash.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A content entity object.
   */
  public function trash(ContentEntityInterface $entity);

  /**
   * Restores an entity from trash.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   A content entity object.
   */
  public function restore(ContentEntityInterface $entity);

}
